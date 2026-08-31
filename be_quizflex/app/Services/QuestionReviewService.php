<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class QuestionReviewService
{
    protected QuestionSnapshotService $snapshotService;
    protected ReportService $reportService;

    public function __construct(
        ?QuestionSnapshotService $snapshotService = null,
        ?ReportService $reportService = null
    ) {
        $this->snapshotService = $snapshotService ?? app(QuestionSnapshotService::class);
        $this->reportService = $reportService ?? app(ReportService::class);
    }

    /**
     * Tác giả gửi yêu cầu duyệt câu hỏi vào Ngân hàng
     */
    public function submitToBank(Question $question, User $user, ?string $requestNote = null): QuestionReviewRequest
    {
        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'question' => 'Bạn không có quyền gửi duyệt câu hỏi này.',
            ]);
        }

        // Kiểm tra câu hỏi phải có ít nhất 2 đáp án và có ít nhất 1 đáp án đúng
        $answers = $question->relationLoaded('answers') ? $question->answers : $question->answers()->orderBy('order')->get();
        if ($answers->count() < 2) {
            throw ValidationException::withMessages([
                'answers' => 'Câu hỏi phải có ít nhất 2 phương án đáp án để có thể gửi duyệt.',
            ]);
        }

        if (!$answers->contains(fn($ans) => (bool)$ans->is_correct)) {
            throw ValidationException::withMessages([
                'answers' => 'Câu hỏi phải có ít nhất 1 đáp án đúng.',
            ]);
        }

        // Kiểm tra xem đã có request PENDING cho câu hỏi này chưa
        $existingPending = QuestionReviewRequest::where('question_id', $question->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            $currentFingerprint = $this->snapshotService->computeFingerprint($question);
            $pendingFingerprint = $this->snapshotService->computeFingerprintFromSnapshot(
                $existingPending->snapshot_content,
                $existingPending->snapshot_type,
                $existingPending->snapshot_answers ?? []
            );

            $hasContentChanged = (
                $currentFingerprint !== $pendingFingerprint ||
                ($question->difficulty ?? 'medium') !== ($existingPending->snapshot_difficulty ?? 'medium') ||
                (int)($question->education_level_id ?? 0) !== (int)($existingPending->snapshot_education_level_id ?? 0) ||
                (int)($question->grade_id ?? 0) !== (int)($existingPending->snapshot_grade_id ?? 0) ||
                (int)($question->subject_id ?? 0) !== (int)($existingPending->snapshot_subject_id ?? 0) ||
                (string)($question->topic_name ?? '') !== (string)($existingPending->snapshot_topic_name ?? '') ||
                (int)($question->points ?? 10) !== (int)($existingPending->snapshot_points ?? 10) ||
                (string)($question->image_url ?? '') !== (string)($existingPending->snapshot_image_url ?? '')
            );

            if (!$hasContentChanged) {
                throw ValidationException::withMessages([
                    'question' => 'Câu hỏi này đang có một yêu cầu chờ Admin phê duyệt và nội dung chưa thay đổi. Vui lòng không gửi lặp lại.',
                ]);
            }
        }

        return DB::transaction(function () use ($question, $user, $requestNote, $answers, $existingPending) {
            if ($existingPending) {
                $existingPending->update([
                    'status' => 'superseded',
                ]);
            }

            $maxRevision = QuestionReviewRequest::where('question_id', $question->id)->max('revision_number') ?? 0;
            $revisionNumber = $maxRevision + 1;

            $snapshotAnswers = $answers->map(function ($ans, $index) {
                return [
                    'id' => $ans->id,
                    'content' => $ans->content,
                    'text' => $ans->content,
                    'is_correct' => (bool) $ans->is_correct,
                    'order' => $ans->order ?? $index,
                    'key' => chr(65 + ($ans->order ?? $index)),
                ];
            })->values()->toArray();

            $question->loadMissing(['educationLevel', 'grade', 'subject']);
            $snapshotMetadata = [
                'education_level_name' => $question->educationLevel?->name,
                'grade_name' => $question->grade?->name,
                'subject_name' => $question->subject?->name,
            ];

            // Tự động nhận diện Question đang có Báo cáo CHƯA GIẢI QUYẾT để đánh dấu PRIORITY
            $hasUnresolvedReports = \App\Models\ReportTicket::where('question_id', $question->id)
                ->whereIn('status', \App\Models\ReportTicket::ACTIVE_STATUSES)
                ->exists();
            $isPriority = $hasUnresolvedReports;
            $reviewPriority = $isPriority ? 'high' : 'normal';

            $latestReport = \App\Models\ReportTicket::where('question_id', $question->id)->latest()->first();
            if ($latestReport) {
                $snapshotMetadata['report_reason'] = $latestReport->reason;
                $snapshotMetadata['report_description'] = $latestReport->description;
                $snapshotMetadata['reports_count'] = \App\Models\ReportTicket::where('question_id', $question->id)->count();
                $snapshotMetadata['has_pending_report'] = $hasUnresolvedReports;
            }

            // Đồng bộ trạng thái Report Lifecycle qua ReportService
            $this->reportService->onAuthorRevisionSubmitted($question, $user);

            $reviewRequest = QuestionReviewRequest::create([
                'question_id' => $question->id,
                'user_id' => $user->id,
                'revision_number' => $revisionNumber,
                'status' => 'pending',
                'review_priority' => $reviewPriority,
                'is_priority' => $isPriority,
                'request_note' => $requestNote ? trim($requestNote) : null,
                'snapshot_content' => $question->content,
                'snapshot_type' => $question->type ?? 'single_choice',
                'snapshot_difficulty' => $question->difficulty ?? 'medium',
                'snapshot_education_level_id' => $question->education_level_id,
                'snapshot_grade_id' => $question->grade_id,
                'snapshot_subject_id' => $question->subject_id,
                'snapshot_topic_name' => $question->topic_name,
                'snapshot_points' => $question->points ?? 10,
                'snapshot_image_url' => $question->image_url,
                'snapshot_answers' => $snapshotAnswers,
                'snapshot_metadata' => $snapshotMetadata,
            ]);

            // Cập nhật trạng thái câu hỏi hiện tại (cached state)
            $fingerprint = $this->snapshotService->computeFingerprint($question);
            Question::where('id', $question->id)->update([
                'fingerprint' => $fingerprint,
                'bank_submission_status' => 'pending',
                'bank_submission_at' => now(),
                'bank_submission_note' => null, // Ghi chú lần trước đã được lưu an toàn trong revision cũ
            ]);
            $question->refresh();

            // Tự động kích hoạt Auto Review nếu câu hỏi có Report chưa giải quyết
            $automationService = app(\App\Services\ReportAutomationService::class);
            if ($automationService->shouldTriggerAutoReview($question)) {
                $autoResult = $automationService->processAutoReviewForQuestion($question, $reviewRequest);
                if ($autoResult['auto_approved'] === true) {
                    return $reviewRequest->fresh(['question', 'user', 'educationLevel', 'grade', 'subject', 'reviewer']);
                }
            } else {
                // Nếu là câu hỏi bình thường không có report, gửi thông báo cho Admin như thường lệ
                $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new QuestionReviewRequested($question, $user, $revisionNumber, $isPriority));
                }
            }

            return $reviewRequest->fresh(['question', 'user', 'educationLevel', 'grade', 'subject']);

        });
    }

    /**
     * Phê duyệt câu hỏi vào Ngân hàng (Hỗ trợ Admin duyệt thủ công và System Auto-Approve)
     */
    public function approveQuestion(Question $question, ?User $admin = null, bool $isAutoApproved = false): QuestionReviewRequest
    {
        if (!$isAutoApproved) {
            if (!$admin || strtolower($admin->role ?? '') !== 'admin') {
                throw ValidationException::withMessages([
                    'auth' => 'Chỉ Admin mới có quyền phê duyệt câu hỏi vào Ngân hàng.',
                ]);
            }
        }

        if ($question->trashed()) {
            throw ValidationException::withMessages([
                'question' => 'Không thể phê duyệt câu hỏi đã bị xóa.',
            ]);
        }

        if ($question->bank_submission_status !== 'pending') {
            throw ValidationException::withMessages([
                'question' => 'Chỉ có thể phê duyệt câu hỏi đang ở trạng thái chờ duyệt (pending).',
            ]);
        }

        return DB::transaction(function () use ($question, $admin, $isAutoApproved) {
            $lockedQuestion = Question::where('id', $question->id)->lockForUpdate()->first();
            if (!$lockedQuestion || $lockedQuestion->bank_submission_status !== 'pending') {
                throw ValidationException::withMessages([
                    'question' => 'Chỉ có thể phê duyệt câu hỏi đang ở trạng thái chờ duyệt (pending).',
                ]);
            }

            $request = QuestionReviewRequest::where('question_id', $lockedQuestion->id)
                ->where('status', 'pending')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$request) {
                throw ValidationException::withMessages([
                    'question' => 'Không tìm thấy yêu cầu xét duyệt đang chờ xử lý cho câu hỏi này.',
                ]);
            }

            $reviewerId = $admin?->id;

            // 1. Tạo bản ghi Question snapshot độc lập trong Ngân hàng từ dữ liệu bất biến của review request (nếu chưa có trong Bank)
            $this->snapshotService->createSnapshotFromReviewRequest($request, $reviewerId);

            // 2. Cập nhật trạng thái của QuestionReviewRequest
            $metadata = $request->snapshot_metadata ?? [];
            if ($isAutoApproved) {
                $metadata['auto_approved'] = true;
                $metadata['auto_approved_at'] = now()->toIso8601String();
            }

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'snapshot_metadata' => $metadata,
            ]);

            // 3. Cập nhật câu hỏi gốc của User: giữ is_public = false để độc lập trong kho cá nhân
            $lockedQuestion->update([
                'is_public' => false,
                'bank_submission_status' => 'approved',
                'bank_submission_note' => null,
            ]);

            // 4. Đồng bộ giải quyết các ReportTicket liên quan qua ReportService
            $reviewer = $reviewerId ? User::find($reviewerId) : null;
            $this->reportService->onQuestionReviewApproved($lockedQuestion, $reviewer, $isAutoApproved);

            $author = $lockedQuestion->user ?? $lockedQuestion->quiz?->user;
            if ($author) {
                $author->notify(new QuestionModerated($lockedQuestion, 'approved'));
            }

            return $request->fresh(['question', 'user', 'reviewer']);
        });
    }


    /**
     * Admin từ chối duyệt câu hỏi vào Ngân hàng kèm lý do
     */
    public function rejectQuestion(Question $question, User $admin, string $reason): QuestionReviewRequest
    {
        if (strtolower($admin->role ?? '') !== 'admin') {
            throw ValidationException::withMessages([
                'auth' => 'Chỉ Admin mới có quyền từ chối câu hỏi.',
            ]);
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'note' => 'Vui lòng nhập lý do từ chối kiểm duyệt câu hỏi.',
            ]);
        }

        if ($question->bank_submission_status !== 'pending') {
            throw ValidationException::withMessages([
                'question' => 'Chỉ có thể từ chối câu hỏi đang ở trạng thái chờ duyệt (pending).',
            ]);
        }

        return DB::transaction(function () use ($question, $admin, $trimmedReason) {
            $request = QuestionReviewRequest::where('question_id', $question->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if (!$request) {
                throw ValidationException::withMessages([
                    'question' => 'Không tìm thấy yêu cầu xét duyệt đang chờ xử lý cho câu hỏi này.',
                ]);
            }

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $trimmedReason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $question->update([
                'is_public' => false,
                'bank_submission_status' => 'rejected',
                'bank_submission_note' => $trimmedReason,
            ]);

            // Chuyển các ReportTicket active của câu hỏi này sang admin_review_required qua ReportService
            $this->reportService->onQuestionReviewRejected($question, $admin, $trimmedReason);

            $author = $question->user ?? $question->quiz?->user;
            if ($author) {
                $author->notify(new QuestionModerated($question, 'rejected', $trimmedReason));
            }

            return $request->fresh(['question', 'user', 'reviewer']);
        });
    }

    /**
     * Lấy chi tiết Revision hiện tại kèm Previous Revision (Dữ liệu cũ để so sánh Diff)
     */
    public function getReviewDetailsWithDiff(Question $question, ?int $requestId = null): array
    {
        $currentRequest = $requestId
            ? QuestionReviewRequest::where('question_id', $question->id)->where('id', $requestId)->first()
            : QuestionReviewRequest::where('question_id', $question->id)->latest('id')->first();

        if (!$currentRequest) {
            return [
                'current_revision' => null,
                'previous_revision' => null,
                'history' => [],
            ];
        }

        $previousRequest = QuestionReviewRequest::where('question_id', $question->id)
            ->where('id', '<', $currentRequest->id)
            ->latest('id')
            ->first();

        $history = QuestionReviewRequest::where('question_id', $question->id)
            ->with(['user:id,name,email,avatar', 'reviewer:id,name,email,avatar'])
            ->orderBy('revision_number', 'desc')
            ->get();

        $reports = \App\Models\ReportTicket::where('question_id', $question->id)
            ->with('user:id,name,email,avatar')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'reporter_name' => $r->user?->name ?? 'Người dùng',
                'reporter_email' => $r->user?->email,
                'reason' => $r->reason,
                'description' => $r->description,
                'status' => $r->status,
                'resolution_source' => $r->resolution_source,
                'resolution_action' => $r->resolution_action,
                'resolved_at' => $r->resolved_at ? $r->resolved_at->toIso8601String() : null,
                'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
            ]);

        $hasActiveReports = \App\Models\ReportTicket::where('question_id', $question->id)
            ->whereIn('status', \App\Models\ReportTicket::ACTIVE_STATUSES)
            ->exists();
        $isPriority = (bool)($currentRequest->is_priority || $currentRequest->review_priority === 'high' || $hasActiveReports);

        return [
            'current_revision' => $this->formatRevision($currentRequest),
            'previous_revision' => $previousRequest ? $this->formatRevision($previousRequest) : null,
            'history' => $history->map(fn($item) => $this->formatRevision($item))->values()->toArray(),
            'reports' => $reports,
            'is_priority' => $isPriority,
            'review_priority' => $isPriority ? 'high' : 'normal',
        ];
    }

    /**
     * Format một bản ghi Revision
     */
    public function formatRevision(QuestionReviewRequest $rev): array
    {
        $hasReport = !empty($rev->snapshot_metadata['report_reason']) || $rev->is_priority || $rev->review_priority === 'high';

        return [
            'id' => $rev->id,
            'question_id' => $rev->question_id,
            'revision_number' => $rev->revision_number,
            'status' => $rev->status,
            'review_priority' => $rev->review_priority ?? ($hasReport ? 'high' : 'normal'),
            'is_priority' => (bool)($rev->is_priority ?? $hasReport),
            'report_reason' => $rev->snapshot_metadata['report_reason'] ?? null,
            'report_description' => $rev->snapshot_metadata['report_description'] ?? null,
            'reports_count' => (int)($rev->snapshot_metadata['reports_count'] ?? 0),
            'request_note' => $rev->request_note,
            'rejection_reason' => $rev->rejection_reason,
            'reviewed_by' => $rev->reviewed_by,
            'reviewed_by_name' => $rev->reviewer?->name,
            'reviewed_at' => $rev->reviewed_at ? $rev->reviewed_at->toIso8601String() : null,
            'created_at' => $rev->created_at ? $rev->created_at->toIso8601String() : null,
            'author_name' => $rev->user?->name,
            'author_email' => $rev->user?->email,
            'content' => $rev->snapshot_content,
            'type' => $rev->snapshot_type,
            'difficulty' => $rev->snapshot_difficulty,
            'education_level_id' => $rev->snapshot_education_level_id,
            'education_level_name' => $rev->snapshot_metadata['education_level_name'] ?? $rev->educationLevel?->name,
            'grade_id' => $rev->snapshot_grade_id,
            'grade_name' => $rev->snapshot_metadata['grade_name'] ?? $rev->grade?->name,
            'subject_id' => $rev->snapshot_subject_id,
            'subject_name' => $rev->snapshot_metadata['subject_name'] ?? $rev->subject?->name,
            'topic_name' => $rev->snapshot_topic_name,
            'points' => $rev->snapshot_points,
            'image_url' => $rev->snapshot_image_url,
            'answers' => $rev->snapshot_answers ?? [],
        ];
    }
}

