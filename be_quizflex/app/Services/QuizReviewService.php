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
     * Tác giả gửi yêu cầu duyệt công khai bài Quiz (Tạo Revision & Snapshot mới)
     */
    public function requestReview(Quiz $quiz, User $user, ?string $requestNote = null): QuizReviewRequest
    {
        if ((int) $quiz->user_id !== (int) $user->id || strtolower($user->role ?? '') === 'admin') {
            throw ValidationException::withMessages([
                'quiz' => 'Bạn không có quyền gửi yêu cầu duyệt bài Quiz này.',
            ]);
        }

        // Kiểm tra Quiz có câu hỏi không
        $quizQuestions = $quiz->questions()->with('answers')->orderBy('quiz_questions.order')->get();
        if ($quizQuestions->isEmpty()) {
            throw ValidationException::withMessages([
                'quiz' => 'Bài Quiz phải có ít nhất 1 câu hỏi để có thể gửi yêu cầu công khai.',
            ]);
        }

        // Kiểm tra tính hợp lệ của câu hỏi trước khi cho phép nộp duyệt
        $this->validateQuizQuestionsForApproval($quiz, $quizQuestions);

        // Không cho phép gửi yêu cầu nếu đã có request PENDING cho Quiz này
        $existingPending = QuizReviewRequest::where('quiz_id', $quiz->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'quiz' => 'Bài Quiz này đang có một yêu cầu chờ Admin phê duyệt. Vui lòng không gửi lặp lại.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $user, $requestNote, $quizQuestions) {
            $maxRevision = QuizReviewRequest::where('quiz_id', $quiz->id)->max('revision_number') ?? 0;
            $revisionNumber = $maxRevision + 1;

            // Xây dựng snapshot_questions (JSON độc lập và bất biến)
            $snapshotQuestions = $quizQuestions->map(function ($q, $index) use ($quiz) {
                $pivot = DB::table('quiz_questions')
                    ->where('quiz_id', $quiz->id)
                    ->where('question_id', $q->id)
                    ->first();

                $order = $pivot?->order ?? $q->order ?? $index;
                $points = $pivot?->points ?? $q->points ?? 10;

                $answers = $q->answers->map(function ($ans, $aIdx) {
                    return [
                        'id' => $ans->id,
                        'content' => $ans->content,
                        'text' => $ans->content,
                        'key' => $ans->answer_key ?? $ans->key ?? chr(65 + ($ans->order ?? $aIdx)),
                        'answer_key' => $ans->answer_key ?? $ans->key ?? chr(65 + ($ans->order ?? $aIdx)),
                        'is_correct' => (bool) $ans->is_correct,
                        'order' => $ans->order ?? $aIdx,
                    ];
                })->values()->toArray();

                return [
                    'question_id' => $q->id,
                    'order' => $order,
                    'points' => $points,
                    'content' => $q->content,
                    'text' => $q->content,
                    'type' => $q->type ?? 'single_choice',
                    'difficulty' => $q->difficulty ?? 'medium',
                    'image_url' => $q->image_url,
                    'source' => $q->is_public ? 'public_bank' : 'my_bank',
                    'education_level_id' => $q->education_level_id,
                    'grade_id' => $q->grade_id,
                    'subject_id' => $q->subject_id,
                    'topic_name' => $q->topic_name,
                    'answers' => $answers,
                ];
            })->values()->toArray();

            $quiz->loadMissing(['educationLevel', 'grade', 'subject', 'user']);
            $snapshotMetadata = [
                'education_level_name' => $quiz->educationLevel?->name,
                'grade_name' => $quiz->grade?->name,
                'subject_name' => $quiz->subject?->name,
                'author_name' => $quiz->user?->name,
                'author_email' => $quiz->user?->email,
                'category' => $quiz->category,
                'tag' => $quiz->tag,
            ];

            $reviewRequest = QuizReviewRequest::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'revision_number' => $revisionNumber,
                'status' => 'pending',
                'request_note' => $requestNote ? trim($requestNote) : null,
                'snapshot_title' => $quiz->title,
                'snapshot_description' => $quiz->description,
                'snapshot_education_level_id' => $quiz->education_level_id,
                'snapshot_grade_id' => $quiz->grade_id,
                'snapshot_subject_id' => $quiz->subject_id,
                'snapshot_topic_name' => $quiz->topic_name,
                'snapshot_time_limit_minutes' => (int) ceil(($quiz->time_limit_seconds ?? 600) / 60),
                'snapshot_shuffle_questions' => true,
                'snapshot_cover' => $quiz->cover,
                'snapshot_questions' => $snapshotQuestions,
                'snapshot_metadata' => $snapshotMetadata,
            ]);

            $quiz->update([
                'review_status' => 'pending_review',
                'rejection_reason' => null,
            ]);

            // Gửi thông báo đến toàn bộ Admin
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new QuizReviewRequested($quiz, $user, $revisionNumber));
            }

            return $reviewRequest->fresh(['quiz', 'user', 'educationLevel', 'grade', 'subject']);
        });
    }

    /**
     * Admin phê duyệt bài Quiz (Tạo snapshot câu hỏi cá nhân vào Bank và công khai Quiz)
     */
    public function approveQuiz(Quiz|QuizReviewRequest $target, User $admin): QuizReviewRequest
    {
        if (strtolower($admin->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'auth' => 'Chỉ Admin mới có quyền phê duyệt bài Quiz.',
            ]);
        }

        $quiz = $target instanceof QuizReviewRequest ? $target->quiz : $target;
        $questions = $quiz->questions()->with('answers')->get();

        // Kiểm tra tính hợp lệ của toàn bộ câu hỏi trước khi duyệt
        $this->validateQuizQuestionsForApproval($quiz, $questions);

        return DB::transaction(function () use ($quiz, $target, $admin, $questions) {
            // 1. Tìm hoặc sử dụng request đang xử lý
            if ($target instanceof QuizReviewRequest) {
                $request = $target;
            } else {
                $request = QuizReviewRequest::where('quiz_id', $quiz->id)
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first();

                if (!$request) {
                    $maxRevision = QuizReviewRequest::where('quiz_id', $quiz->id)->max('revision_number') ?? 0;
                    $request = QuizReviewRequest::create([
                        'quiz_id' => $quiz->id,
                        'user_id' => $quiz->user_id ?? $admin->id,
                        'revision_number' => $maxRevision + 1,
                        'status' => 'pending',
                        'snapshot_title' => $quiz->title,
                        'snapshot_description' => $quiz->description,
                    ]);
                }
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

                // Nếu câu hỏi chưa thuộc Ngân hàng công khai
                if (!$question->is_public) {
                    $snapshot = $this->snapshotService->createSnapshotForBank($question, $admin->id);
                    $targetQuestionId = $snapshot->id;

                    $pivotSyncData[$targetQuestionId] = [
                        'order' => $order,
                        'points' => $points,
                    ];
                } else {
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
    public function rejectQuiz(Quiz|QuizReviewRequest $target, User $admin, string $reason): QuizReviewRequest
    {
        if (strtolower($admin->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'auth' => 'Chỉ Admin mới có quyền từ chối phê duyệt bài Quiz.',
            ]);
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do từ chối kiểm duyệt bài Quiz.',
            ]);
        }

        $quiz = $target instanceof QuizReviewRequest ? $target->quiz : $target;

        return DB::transaction(function () use ($quiz, $target, $admin, $trimmedReason) {
            if ($target instanceof QuizReviewRequest) {
                $request = $target;
            } else {
                $request = QuizReviewRequest::where('quiz_id', $quiz->id)
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first();

                if (!$request) {
                    $maxRevision = QuizReviewRequest::where('quiz_id', $quiz->id)->max('revision_number') ?? 0;
                    $request = QuizReviewRequest::create([
                        'quiz_id' => $quiz->id,
                        'user_id' => $quiz->user_id ?? $admin->id,
                        'revision_number' => $maxRevision + 1,
                        'status' => 'pending',
                        'snapshot_title' => $quiz->title,
                        'snapshot_description' => $quiz->description,
                    ]);
                }
            }

            $quiz->update([
                'is_public' => false,
                'review_status' => 'rejected',
                'rejection_reason' => $trimmedReason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $trimmedReason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Gửi thông báo cho tác giả
            $author = $quiz->user ?? User::find($quiz->user_id);
            if ($author) {
                $author->notify(new QuizModerated($quiz, 'rejected', $trimmedReason));
            }

            return $request->fresh(['quiz', 'user', 'reviewer']);
        });
    }

    /**
     * Lấy chi tiết Revision hiện tại kèm Previous Revision và Diff (So sánh Cũ vs Mới)
     */
    public function getReviewDetailsWithDiff(Quiz|QuizReviewRequest $target, ?int $requestId = null): array
    {
        if ($target instanceof QuizReviewRequest) {
            $currentRequest = $target;
            $quizId = $target->quiz_id;
        } else {
            $quizId = $target->id;
            $currentRequest = $requestId
                ? QuizReviewRequest::where('quiz_id', $quizId)->where('id', $requestId)->first()
                : QuizReviewRequest::where('quiz_id', $quizId)->latest('id')->first();
        }

        if (!$currentRequest) {
            return [
                'current_revision' => null,
                'previous_revision' => null,
                'diff' => null,
                'history' => [],
            ];
        }

        $previousRequest = QuizReviewRequest::where('quiz_id', $quizId)
            ->where('id', '<', $currentRequest->id)
            ->latest('id')
            ->first();

        $history = QuizReviewRequest::where('quiz_id', $quizId)
            ->with(['user:id,name,email,avatar', 'reviewer:id,name,email,avatar'])
            ->orderBy('revision_number', 'desc')
            ->get();

        $diff = $this->calculateDiff($currentRequest, $previousRequest);

        return [
            'current_revision' => $this->formatRevision($currentRequest),
            'previous_revision' => $previousRequest ? $this->formatRevision($previousRequest) : null,
            'previous_rejection_reason' => $previousRequest?->rejection_reason,
            'diff' => $diff,
            'history' => $history->map(fn($item) => $this->formatRevision($item))->values()->toArray(),
        ];
    }

    /**
     * Tính toán sai khác (Diff) giữa Revision hiện tại và Revision trước
     */
    protected function calculateDiff(QuizReviewRequest $current, ?QuizReviewRequest $previous): array
    {
        if (!$previous) {
            return [
                'has_previous' => false,
                'metadata_changed' => false,
                'changes' => [],
                'questions_summary' => [
                    'total_current' => count($current->snapshot_questions ?? []),
                    'added_count' => count($current->snapshot_questions ?? []),
                    'removed_count' => 0,
                    'modified_count' => 0,
                    'unchanged_count' => 0,
                ],
            ];
        }

        $changes = [];
        if ($current->snapshot_title !== $previous->snapshot_title) {
            $changes['title'] = ['label' => 'Tiêu đề Quiz', 'old' => $previous->snapshot_title, 'new' => $current->snapshot_title];
        }
        if ($current->snapshot_description !== $previous->snapshot_description) {
            $changes['description'] = ['label' => 'Mô tả', 'old' => $previous->snapshot_description, 'new' => $current->snapshot_description];
        }
        if ($current->snapshot_topic_name !== $previous->snapshot_topic_name) {
            $changes['topic_name'] = ['label' => 'Chủ đề', 'old' => $previous->snapshot_topic_name, 'new' => $current->snapshot_topic_name];
        }
        if ($current->snapshot_subject_id !== $previous->snapshot_subject_id) {
            $changes['subject'] = [
                'label' => 'Môn học',
                'old' => $previous->snapshot_metadata['subject_name'] ?? null,
                'new' => $current->snapshot_metadata['subject_name'] ?? null,
            ];
        }
        if ($current->snapshot_grade_id !== $previous->snapshot_grade_id) {
            $changes['grade'] = [
                'label' => 'Khối lớp',
                'old' => $previous->snapshot_metadata['grade_name'] ?? null,
                'new' => $current->snapshot_metadata['grade_name'] ?? null,
            ];
        }
        if ($current->snapshot_education_level_id !== $previous->snapshot_education_level_id) {
            $changes['education_level'] = [
                'label' => 'Cấp học',
                'old' => $previous->snapshot_metadata['education_level_name'] ?? null,
                'new' => $current->snapshot_metadata['education_level_name'] ?? null,
            ];
        }
        if ($current->snapshot_time_limit_minutes !== $previous->snapshot_time_limit_minutes) {
            $changes['time_limit_minutes'] = [
                'label' => 'Thời gian làm bài',
                'old' => $previous->snapshot_time_limit_minutes ? "{$previous->snapshot_time_limit_minutes} phút" : null,
                'new' => $current->snapshot_time_limit_minutes ? "{$current->snapshot_time_limit_minutes} phút" : null,
            ];
        }
        if ((bool)$current->snapshot_shuffle_questions !== (bool)$previous->snapshot_shuffle_questions) {
            $changes['shuffle_questions'] = [
                'label' => 'Xáo trộn câu hỏi',
                'old' => $previous->snapshot_shuffle_questions ? 'Bật' : 'Tắt',
                'new' => $current->snapshot_shuffle_questions ? 'Bật' : 'Tắt',
            ];
        }

        $oldQuestions = collect($previous->snapshot_questions ?? []);
        $newQuestions = collect($current->snapshot_questions ?? []);

        $addedCount = 0;
        $removedCount = 0;
        $modifiedCount = 0;
        $unchangedCount = 0;

        $oldMap = $oldQuestions->keyBy(fn($q) => $q['question_id'] ?? $q['id'] ?? md5($q['content'] ?? ''));
        $newMap = $newQuestions->keyBy(fn($q) => $q['question_id'] ?? $q['id'] ?? md5($q['content'] ?? ''));

        $questionDiffs = [];

        // Check for Modified & Added Questions
        foreach ($newQuestions as $index => $nQ) {
            $key = $nQ['question_id'] ?? $nQ['id'] ?? md5($nQ['content'] ?? '');
            if ($oldMap->has($key)) {
                $oQ = $oldMap->get($key);

                $fieldChanges = [];
                if (($nQ['content'] ?? '') !== ($oQ['content'] ?? '')) {
                    $fieldChanges['content'] = ['label' => 'Nội dung câu hỏi', 'old' => $oQ['content'] ?? '', 'new' => $nQ['content'] ?? ''];
                }
                if (($nQ['type'] ?? 'single_choice') !== ($oQ['type'] ?? 'single_choice')) {
                    $fieldChanges['type'] = ['label' => 'Loại câu hỏi', 'old' => $oQ['type'] ?? 'single_choice', 'new' => $nQ['type'] ?? 'single_choice'];
                }
                if (($nQ['points'] ?? 10) !== ($oQ['points'] ?? 10)) {
                    $fieldChanges['points'] = ['label' => 'Điểm số', 'old' => $oQ['points'] ?? 10, 'new' => $nQ['points'] ?? 10];
                }
                if (($nQ['difficulty'] ?? 'medium') !== ($oQ['difficulty'] ?? 'medium')) {
                    $fieldChanges['difficulty'] = ['label' => 'Độ khó', 'old' => $oQ['difficulty'] ?? 'medium', 'new' => $nQ['difficulty'] ?? 'medium'];
                }

                // Check answer diffs
                $oldAns = collect($oQ['answers'] ?? []);
                $newAns = collect($nQ['answers'] ?? []);

                $answerChanges = [];
                $oldAnsMap = $oldAns->keyBy(fn($a, $aIdx) => $a['id'] ?? $a['key'] ?? $a['answer_key'] ?? $aIdx);
                $newAnsMap = $newAns->keyBy(fn($a, $aIdx) => $a['id'] ?? $a['key'] ?? $a['answer_key'] ?? $aIdx);

                // Modified or added answers
                foreach ($newAns as $aIdx => $na) {
                    $aKey = $na['id'] ?? $na['key'] ?? $na['answer_key'] ?? $aIdx;
                    if ($oldAnsMap->has($aKey)) {
                        $oa = $oldAnsMap->get($aKey);
                        $contentDiff = ($na['content'] ?? '') !== ($oa['content'] ?? '');
                        $correctDiff = (bool)($na['is_correct'] ?? false) !== (bool)($oa['is_correct'] ?? false);
                        if ($contentDiff || $correctDiff) {
                            $answerChanges[] = [
                                'status' => 'modified',
                                'key' => $na['key'] ?? $na['answer_key'] ?? chr(65 + $aIdx),
                                'old_content' => $oa['content'] ?? '',
                                'new_content' => $na['content'] ?? '',
                                'old_is_correct' => (bool)($oa['is_correct'] ?? false),
                                'new_is_correct' => (bool)($na['is_correct'] ?? false),
                            ];
                        }
                    } else {
                        $answerChanges[] = [
                            'status' => 'added',
                            'key' => $na['key'] ?? $na['answer_key'] ?? chr(65 + $aIdx),
                            'new_content' => $na['content'] ?? '',
                            'new_is_correct' => (bool)($na['is_correct'] ?? false),
                        ];
                    }
                }

                // Removed answers
                foreach ($oldAns as $aIdx => $oa) {
                    $aKey = $oa['id'] ?? $oa['key'] ?? $oa['answer_key'] ?? $aIdx;
                    if (!$newAnsMap->has($aKey)) {
                        $answerChanges[] = [
                            'status' => 'removed',
                            'key' => $oa['key'] ?? $oa['answer_key'] ?? chr(65 + $aIdx),
                            'old_content' => $oa['content'] ?? '',
                            'old_is_correct' => (bool)($oa['is_correct'] ?? false),
                        ];
                    }
                }

                if (!empty($fieldChanges) || !empty($answerChanges)) {
                    $modifiedCount++;
                    $questionDiffs[] = [
                        'status' => 'modified',
                        'question_id' => $nQ['question_id'] ?? $nQ['id'] ?? null,
                        'order' => $index + 1,
                        'content' => $nQ['content'] ?? $oQ['content'] ?? '',
                        'field_changes' => $fieldChanges,
                        'answer_changes' => $answerChanges,
                        'old_question' => $oQ,
                        'new_question' => $nQ,
                    ];
                } else {
                    $unchangedCount++;
                }
            } else {
                $addedCount++;
                $questionDiffs[] = [
                    'status' => 'added',
                    'question_id' => $nQ['question_id'] ?? $nQ['id'] ?? null,
                    'order' => $index + 1,
                    'content' => $nQ['content'] ?? '',
                    'new_question' => $nQ,
                ];
            }
        }

        // Check for Removed Questions
        foreach ($oldQuestions as $index => $oQ) {
            $key = $oQ['question_id'] ?? $oQ['id'] ?? md5($oQ['content'] ?? '');
            if (!$newMap->has($key)) {
                $removedCount++;
                $questionDiffs[] = [
                    'status' => 'removed',
                    'question_id' => $oQ['question_id'] ?? $oQ['id'] ?? null,
                    'order' => $index + 1,
                    'content' => $oQ['content'] ?? '',
                    'old_question' => $oQ,
                ];
            }
        }

        $hasDifferences = !empty($changes) || $addedCount > 0 || $removedCount > 0 || $modifiedCount > 0;

        return [
            'has_previous' => true,
            'has_differences' => $hasDifferences,
            'metadata_changed' => !empty($changes),
            'changes' => $changes,
            'questions_summary' => [
                'total_current' => $newQuestions->count(),
                'total_previous' => $oldQuestions->count(),
                'added_count' => $addedCount,
                'removed_count' => $removedCount,
                'modified_count' => $modifiedCount,
                'unchanged_count' => $unchangedCount,
            ],
            'question_diffs' => $questionDiffs,
        ];
    }

    /**
     * Format một bản ghi Revision
     */
    public function formatRevision(QuizReviewRequest $rev): array
    {
        return [
            'id' => $rev->id,
            'quiz_id' => $rev->quiz_id,
            'revision_number' => $rev->revision_number,
            'status' => $rev->status,
            'request_note' => $rev->request_note,
            'rejection_reason' => $rev->rejection_reason,
            'reviewed_by' => $rev->reviewed_by,
            'reviewed_by_name' => $rev->reviewer?->name,
            'reviewed_at' => $rev->reviewed_at ? $rev->reviewed_at->toIso8601String() : null,
            'created_at' => $rev->created_at ? $rev->created_at->toIso8601String() : null,
            'author_name' => $rev->user?->name ?? $rev->snapshot_metadata['author_name'] ?? null,
            'author_email' => $rev->user?->email ?? $rev->snapshot_metadata['author_email'] ?? null,
            'title' => $rev->snapshot_title,
            'description' => $rev->snapshot_description,
            'education_level_id' => $rev->snapshot_education_level_id,
            'education_level_name' => $rev->snapshot_metadata['education_level_name'] ?? null,
            'grade_id' => $rev->snapshot_grade_id,
            'grade_name' => $rev->snapshot_metadata['grade_name'] ?? null,
            'subject_id' => $rev->snapshot_subject_id,
            'subject_name' => $rev->snapshot_metadata['subject_name'] ?? null,
            'topic_name' => $rev->snapshot_topic_name,
            'time_limit_minutes' => $rev->snapshot_time_limit_minutes,
            'shuffle_questions' => (bool) $rev->snapshot_shuffle_questions,
            'cover' => $rev->snapshot_cover,
            'questions' => $rev->snapshot_questions ?? [],
            'questions_count' => count($rev->snapshot_questions ?? []),
        ];
    }

    /**
     * Kiểm tra tính hợp lệ toàn diện của câu hỏi trước khi cho phép Approve / Submit Quiz
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
