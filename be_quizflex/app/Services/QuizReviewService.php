<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizReviewRequest;
use App\Models\User;
use App\Notifications\QuizModerated;
use App\Notifications\QuizReviewRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class QuizReviewService
{
    public function __construct(
        protected QuestionSnapshotService $snapshotService
    ) {}

    /**
     * Tác giả gửi yêu cầu duyệt công khai bài Quiz
     */
    public function requestReview(Quiz $quiz, User $user, ?string $requestNote = null): QuizReviewRequest
    {
        if ($quiz->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'quiz' => 'Bạn không có quyền gửi yêu cầu duyệt bài Quiz này.',
            ]);
        }

        // Kiểm tra Quiz có câu hỏi không
        $questionCount = $quiz->questions()->count();
        if ($questionCount === 0) {
            throw ValidationException::withMessages([
                'quiz' => 'Bài Quiz phải có ít nhất 1 câu hỏi để có thể gửi yêu cầu công khai.',
            ]);
        }

        // Không cho phép gửi yêu cầu nếu đã có request PENDING
        $existingPending = QuizReviewRequest::where('quiz_id', $quiz->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'quiz' => 'Bài Quiz này đang có một yêu cầu chờ Admin phê duyệt. Vui lòng không gửi lặp lại.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $user, $requestNote) {
            $quiz->update([
                'review_status' => 'pending_review',
                'rejection_reason' => null,
            ]);

            $request = QuizReviewRequest::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'request_note' => $requestNote ? trim($requestNote) : null,
            ]);

            // Gửi thông báo đến Admin
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new QuizReviewRequested($quiz, $user));
            }

            return $request->fresh(['quiz', 'user']);
        });
    }

    /**
     * Admin phê duyệt bài Quiz (Tạo snapshot cho câu hỏi cá nhân và Public Quiz)
     */
    public function approveQuiz(Quiz $quiz, User $admin): QuizReviewRequest
    {
        if (strtolower($admin->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'auth' => 'Chỉ Admin mới có quyền phê duyệt bài Quiz.',
            ]);
        }

        $questions = $quiz->questions()->with('answers')->get();

        // Kiểm tra tính hợp lệ của toàn bộ câu hỏi trước khi duyệt (Một câu không đạt -> Reject)
        $this->validateQuizQuestionsForApproval($quiz, $questions);

        return DB::transaction(function () use ($quiz, $admin, $questions) {
            // 1. Tìm hoặc tạo request đang pending
            $request = QuizReviewRequest::where('quiz_id', $quiz->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$request) {
                $request = QuizReviewRequest::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => $quiz->user_id,
                    'status' => 'pending',
                ]);
            }

            // 2. Snapshot câu hỏi từ kho cá nhân vào Ngân hàng câu hỏi
            $pivotSyncData = [];

            foreach ($questions as $question) {
                $currentPivot = DB::table('quiz_questions')
                    ->where('quiz_id', $quiz->id)
                    ->where('question_id', $question->id)
                    ->first();

                $order = $currentPivot?->order ?? $question->order ?? 0;
                $points = $currentPivot?->points ?? $question->points ?? 10;

                // Nếu câu hỏi chưa thuộc Ngân hàng công khai (câu hỏi cá nhân của user)
                if (!$question->is_public) {
                    $fingerprint = $this->snapshotService->computeFingerprint($question);

                    if (empty($question->fingerprint) || $question->fingerprint !== $fingerprint) {
                        $question->update(['fingerprint' => $fingerprint]);
                    }

                    $existingBankQuestion = $this->snapshotService->findExistingBankQuestion($fingerprint);

                    if ($existingBankQuestion) {
                        // Đã có câu hỏi giống hệt trong Bank -> Tái sử dụng ID câu hỏi trong Bank
                        $targetQuestionId = $existingBankQuestion->id;
                    } else {
                        // Chưa có -> Tạo bản snapshot mới vào Ngân hàng
                        $snapshot = $this->snapshotService->createSnapshotForBank($question, $admin->id);
                        $targetQuestionId = $snapshot->id;
                    }

                    $pivotSyncData[$targetQuestionId] = [
                        'order' => $order,
                        'points' => $points,
                    ];
                } else {
                    // Câu hỏi đã là public trong Ngân hàng
                    if (empty($question->fingerprint)) {
                        $question->update([
                            'fingerprint' => $this->snapshotService->computeFingerprint($question),
                        ]);
                    }

                    $pivotSyncData[$question->id] = [
                        'order' => $order,
                        'points' => $points,
                    ];
                }
            }

            // Đồng bộ lại pivot table quiz_questions trỏ sang các câu hỏi công khai đã duyệt
            $quiz->questions()->sync($pivotSyncData);

            // 3. Cập nhật trạng thái Quiz
            $quiz->update([
                'is_public' => true,
                'status' => 'published',
                'review_status' => 'approved',
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // 4. Cập nhật QuizReviewRequest
            $request->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // 5. Gửi thông báo cho tác giả bài Quiz
            $author = $quiz->user ?? User::find($quiz->user_id);
            if ($author) {
                $author->notify(new QuizModerated($quiz, 'approved'));
            }

            return $request->fresh(['quiz', 'user', 'reviewer']);
        });
    }

    /**
     * Admin từ chối phê duyệt bài Quiz
     */
    public function rejectQuiz(Quiz $quiz, User $admin, string $reason): QuizReviewRequest
    {
        if (strtolower($admin->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'auth' => 'Chỉ Admin mới có quyền từ chối phê duyệt bài Quiz.',
            ]);
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do từ chối kiểm duyệt bài Quiz.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $admin, $reason) {
            $request = QuizReviewRequest::where('quiz_id', $quiz->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$request) {
                $request = QuizReviewRequest::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => $quiz->user_id,
                    'status' => 'pending',
                ]);
            }

            $quiz->update([
                'is_public' => false,
                'review_status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Gửi thông báo cho tác giả
            $author = $quiz->user ?? User::find($quiz->user_id);
            if ($author) {
                $author->notify(new QuizModerated($quiz, 'rejected', $reason));
            }

            return $request->fresh(['quiz', 'user', 'reviewer']);
        });
    }

    /**
     * Kiểm tra tính hợp lệ toàn diện của câu hỏi trước khi cho phép Approve Quiz
     * Áp dụng nguyên tắc: MỘT CÂU KHÔNG ĐẠT -> TOÀN BỘ YÊU CẦU BỊ TỪ CHỐI / KHÔNG CHO APPROVE
     */
    public function validateQuizQuestionsForApproval(Quiz $quiz, $questions): void
    {
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'quiz' => 'Bài Quiz không có câu hỏi nào để phê duyệt.',
            ]);
        }

        foreach ($questions as $index => $question) {
            $qNumber = $index + 1;
            $cleanContent = trim(strip_tags($question->content ?? ''));
            if (empty($cleanContent)) {
                throw ValidationException::withMessages([
                    'quiz' => "Câu hỏi số {$qNumber} không hợp lệ: Nội dung câu hỏi không được để trống.",
                ]);
            }

            $answers = $question->answers;
            if ($answers->count() < 2) {
                throw ValidationException::withMessages([
                    'quiz' => "Câu hỏi số {$qNumber} không hợp lệ: Phải có ít nhất 2 đáp án lựa chọn.",
                ]);
            }

            foreach ($answers as $aIndex => $ans) {
                $char = chr(65 + $aIndex);
                $ansContent = trim(strip_tags($ans->content ?? ''));
                if (empty($ansContent)) {
                    throw ValidationException::withMessages([
                        'quiz' => "Câu hỏi số {$qNumber} (Đáp án {$char}) không hợp lệ: Nội dung đáp án không được để trống.",
                    ]);
                }
            }

            $correctAnswersCount = $answers->where('is_correct', true)->count();
            if ($correctAnswersCount === 0) {
                throw ValidationException::withMessages([
                    'quiz' => "Câu hỏi số {$qNumber} không hợp lệ: Chưa có đáp án đúng nào được chọn.",
                ]);
            }

            if (($question->type ?? 'single_choice') === 'single_choice' && $correctAnswersCount > 1) {
                throw ValidationException::withMessages([
                    'quiz' => "Câu hỏi số {$qNumber} dạng trắc nghiệm một đáp án nhưng có {$correctAnswersCount} đáp án đúng.",
                ]);
            }
        }
    }
}
