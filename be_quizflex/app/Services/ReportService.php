<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportCreated;
use App\Notifications\ReportResolved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Người dùng tạo báo cáo vi phạm câu hỏi (Public Question / Bank Snapshot / Public Quiz)
     */
    public function createReport(User $reporter, int $questionId, string $reason, ?string $description = null): ReportTicket
    {
        $targetQuestion = Question::with(['user', 'quiz.user', 'quizzes'])->find($questionId);
        if (!$targetQuestion) {
            throw ValidationException::withMessages([
                'question_id' => 'Không tìm thấy câu hỏi được báo cáo.',
            ]);
        }

        // Kiểm tra câu hỏi có hợp lệ để báo cáo không
        $isPublicQuestion = (bool) $targetQuestion->is_public;
        $isBankSnapshot = !empty($targetQuestion->origin_question_id);
        $isAttachedToPublicQuiz = ($targetQuestion->quiz && $targetQuestion->quiz->is_public && $targetQuestion->quiz->status === 'published')
            || $targetQuestion->quizzes()->where('is_public', true)->where('status', 'published')->exists();

        if (!$isPublicQuestion && !$isBankSnapshot && !$isAttachedToPublicQuiz) {
            throw ValidationException::withMessages([
                'question' => 'Chỉ có thể báo cáo câu hỏi công khai hoặc trong bài thi công khai.',
            ]);
        }

        // Phân giải Snapshot về Question gốc và Owner
        $originQuestion = $targetQuestion->origin_question_id
            ? (Question::with(['user', 'quiz.user'])->find($targetQuestion->origin_question_id) ?? $targetQuestion)
            : $targetQuestion;

        // Chống duplicate report: Kiểm tra xem User đã có ticket active cho câu hỏi này chưa
        $existingPending = ReportTicket::where('user_id', $reporter->id)
            ->whereIn('question_id', array_unique([$targetQuestion->id, $originQuestion->id]))
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'report' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ]);
        }

        $reasonText = trim($reason);
        $unresolvedCount = ReportTicket::where('question_id', $originQuestion->id)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->count();

        $initialStatus = ReportTicket::determineInitialStatus($reasonText, $unresolvedCount);

        // Thực hiện ghi nhận DB trong Transaction
        $report = DB::transaction(function () use ($reporter, $originQuestion, $reasonText, $description, $initialStatus, $unresolvedCount) {
            $ticket = ReportTicket::create([
                'user_id' => $reporter->id,
                'question_id' => $originQuestion->id,
                'reason' => $reasonText,
                'description' => !empty($description) ? trim($description) : null,
                'status' => $initialStatus,
            ]);

            // Nếu vượt ngưỡng >= 3 reports, nâng cấp toàn bộ report pending cũ sang admin_review_required
            if ($unresolvedCount >= (ReportTicket::MULTI_REPORT_THRESHOLD - 1)) {
                ReportTicket::where('question_id', $originQuestion->id)
                    ->where('status', ReportTicket::STATUS_PENDING)
                    ->update(['status' => ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED]);
            }

            return $ticket;
        });

        // =========================================================================
        // SIDE EFFECTS: Gửi Notification sau khi DB Transaction đã commit an toàn
        // Áp dụng nguyên tắc Case-level: Không spam khi câu hỏi đã có Case đang mở
        // =========================================================================
        // 1. Gửi thông báo cho đúng Owner của Question gốc khi đây là report đầu tiên mở Case
        $questionOwner = $originQuestion->user ?? $originQuestion->quiz?->user;
        if ($questionOwner && $questionOwner->id !== $reporter->id && $unresolvedCount === 0) {
            try {
                $questionOwner->notify(new QuestionModerated($originQuestion, 'reported', $report->reason, $report->description));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo QuestionModerated cho tác giả: ' . $e->getMessage());
            }
        }

        // 2. Gửi thông báo cho Admin khi:
        // - Đây là report đầu tiên của Case ($unresolvedCount === 0); HOẶC
        // - Report này làm Case escalate lên needs_admin (vượt ngưỡng >= 3 reports hoặc lý do nghiêm trọng)
        $isEscalatedToAdmin = ($initialStatus === ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED)
            || ($unresolvedCount >= (ReportTicket::MULTI_REPORT_THRESHOLD - 1));
        $shouldNotifyAdmin = ($unresolvedCount === 0) || $isEscalatedToAdmin;

        if ($shouldNotifyAdmin) {
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                try {
                    Notification::send($admins, new ReportCreated($report, $reporter));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo ReportCreated cho Admin: ' . $e->getMessage());
                }
            }
        }

        return $report;
    }

    /**
     * Admin giải quyết toàn bộ các báo cáo vi phạm của một Question Case
     */
    public function resolveQuestionReports(int $questionId, string $status, string $action = 'keep', $admin = null, ?string $adminNote = null): array
    {
        $adminId = $admin instanceof User ? $admin->id : (is_numeric($admin) ? (int) $admin : null);
        $activeTicketsToNotify = [];
        $question = Question::withTrashed()->with(['user', 'quiz.user'])->find($questionId);

        if (!$question) {
            throw ValidationException::withMessages([
                'question_id' => 'Không tìm thấy câu hỏi tương ứng.',
            ]);
        }

        DB::transaction(function () use ($questionId, $status, $action, $adminId, $adminNote, $question, &$activeTicketsToNotify) {
            $activeTickets = ReportTicket::with('user')
                ->where('question_id', $questionId)
                ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
                ->get();

            foreach ($activeTickets as $ticket) {
                if ($status === ReportTicket::STATUS_RESOLVED) {
                    $ticket->markResolved(
                        source: ReportTicket::RESOLUTION_SOURCE_ADMIN,
                        action: $action,
                        resolvedBy: $adminId,
                        note: $adminNote
                    );
                } elseif ($status === ReportTicket::STATUS_DISMISSED) {
                    $ticket->markDismissed(
                        source: ReportTicket::RESOLUTION_SOURCE_ADMIN,
                        resolvedBy: $adminId,
                        note: $adminNote
                    );
                } else {
                    $ticket->transitionTo($status);
                }

                $activeTicketsToNotify[] = $ticket;
            }

            // Thực hiện tác động lên Question nếu có
            if ($action === 'hide') {
                $question->is_public = false;
                $question->save();
            } elseif ($action === 'delete') {
                if (!$question->trashed()) {
                    $question->delete();
                }
            }
        });

        // =========================================================================
        // SIDE EFFECTS: Gửi Notification sau khi DB Transaction đã commit an toàn
        // =========================================================================
        if (in_array($status, [ReportTicket::STATUS_RESOLVED, ReportTicket::STATUS_DISMISSED], true)) {
            // Gửi thông báo cho từng Reporter (Deduplicate theo user_id tránh spam nếu cùng user có nhiều ticket)
            $notifiedReporterIds = [];
            foreach ($activeTicketsToNotify as $ticket) {
                if ($ticket->user && !in_array($ticket->user_id, $notifiedReporterIds, true)) {
                    $notifiedReporterIds[] = $ticket->user_id;
                    try {
                        $ticket->user->notify(new ReportResolved($ticket, $status, $action));
                    } catch (\Throwable $e) {
                        Log::warning('Không thể gửi thông báo ReportResolved cho reporter: ' . $e->getMessage());
                    }
                }
            }

            // Gửi thông báo cho Author
            $author = $question->user ?? $question->quiz?->user;
            if ($author) {
                $moderatedAction = $action === 'delete'
                    ? 'deleted'
                    : ($action === 'hide' ? 'hidden' : ($status === ReportTicket::STATUS_DISMISSED ? 'dismissed' : 'resolved'));

                $moderatedReason = $adminNote ?? ($status === ReportTicket::STATUS_DISMISSED
                    ? 'Báo cáo vi phạm đã được quản trị viên kiểm tra và bỏ qua (không có vi phạm).'
                    : 'Báo cáo vi phạm đã được quản trị viên xử lý.');

                try {
                    $author->notify(new QuestionModerated($question, $moderatedAction, $moderatedReason));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo xử lý report cho tác giả: ' . $e->getMessage());
                }
            }
        }

        return [
            'success' => true,
            'message' => $status === ReportTicket::STATUS_RESOLVED
                ? 'Đã giải quyết tất cả báo cáo cho câu hỏi này.'
                : ($status === ReportTicket::STATUS_DISMISSED ? 'Đã bỏ qua các báo cáo.' : 'Đã cập nhật trạng thái báo cáo.'),
            'question_id' => $questionId,
            'resolved_tickets_count' => count($activeTicketsToNotify),
        ];
    }

    /**
     * Cập nhật trạng thái của một báo cáo vi phạm đơn lẻ
     */
    public function updateSingleTicketStatus(ReportTicket $ticket, string $targetStatus, ?User $actor = null, ?string $note = null): ReportTicket
    {
        if (!$ticket->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển trạng thái báo cáo từ '{$ticket->status}' sang '{$targetStatus}' do vi phạm quy tắc chuyển trạng thái.",
            ]);
        }

        $actorId = $actor?->id;

        DB::transaction(function () use ($ticket, $targetStatus, $actorId, $note) {
            if ($targetStatus === ReportTicket::STATUS_RESOLVED) {
                $ticket->markResolved(
                    source: ReportTicket::RESOLUTION_SOURCE_ADMIN,
                    action: ReportTicket::RESOLUTION_ACTION_APPROVED,
                    resolvedBy: $actorId,
                    note: $note
                );
            } elseif ($targetStatus === ReportTicket::STATUS_DISMISSED) {
                $ticket->markDismissed(
                    source: ReportTicket::RESOLUTION_SOURCE_ADMIN,
                    resolvedBy: $actorId,
                    note: $note
                );
            } else {
                $ticket->transitionTo($targetStatus);
            }
        });

        // SIDE EFFECTS
        if (in_array($targetStatus, [ReportTicket::STATUS_RESOLVED, ReportTicket::STATUS_DISMISSED], true)) {
            if ($ticket->user) {
                try {
                    $ticket->user->notify(new ReportResolved($ticket, $targetStatus, 'approved'));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo ReportResolved cho reporter: ' . $e->getMessage());
                }
            }
        }

        return $ticket->fresh(['user', 'resolver', 'question']);
    }

    /**
     * Lifecycle Hook: Khi tác giả chỉnh sửa câu hỏi hoặc gửi Revision mới
     */
    public function onAuthorRevisionSubmitted(Question $question, User $author): void
    {
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $activeReports = ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', [ReportTicket::STATUS_PENDING, ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED])
            ->get();

        if ($activeReports->isEmpty()) {
            return;
        }

        foreach ($activeReports as $rep) {
            $rep->has_author_updated = true;
            $rep->transitionTo(ReportTicket::STATUS_AUTHOR_UPDATED);
        }

        // Gửi thông báo cho Quản trị viên về việc tác giả đã đính chính nội dung bị báo cáo
        $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new ReportAuthorUpdated($question, 'question', $author));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo ReportAuthorUpdated cho Admin: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lifecycle Hook: Khi câu hỏi được Admin hoặc Auto Review phê duyệt (Approved)
     */
    public function onQuestionReviewApproved(Question $question, ?User $reviewer = null, bool $isAutoApproved = false): void
    {
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $unresolvedReports = ReportTicket::with('user')
            ->whereIn('question_id', $allRelatedIds)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->get();

        $resSource = $isAutoApproved
            ? ReportTicket::RESOLUTION_SOURCE_AUTO_REVIEW
            : ReportTicket::RESOLUTION_SOURCE_ADMIN;

        $reviewerId = $reviewer?->id;

        $notifiedReporterIds = [];
        foreach ($unresolvedReports as $rep) {
            $rep->markResolved(
                source: $resSource,
                action: ReportTicket::RESOLUTION_ACTION_APPROVED,
                resolvedBy: $reviewerId,
                note: $isAutoApproved ? 'Tự động duyệt sau khi tác giả đính chính đạt chuẩn.' : 'Phê duyệt vào Ngân hàng câu hỏi.'
            );

            if ($rep->user && !in_array($rep->user_id, $notifiedReporterIds, true)) {
                $notifiedReporterIds[] = $rep->user_id;
                try {
                    $rep->user->notify(new ReportResolved($rep, 'resolved', 'approved'));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo ReportResolved: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Lifecycle Hook: Khi yêu cầu xét duyệt câu hỏi bị Admin từ chối (Rejected)
     */
    public function onQuestionReviewRejected(Question $question, User $reviewer, string $reason): void
    {
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $activeReports = ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', [ReportTicket::STATUS_PENDING, ReportTicket::STATUS_AUTHOR_UPDATED])
            ->get();

        foreach ($activeReports as $rep) {
            $rep->transitionTo(ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED);
        }
    }

    /**
     * Lifecycle Hook: Khi quy trình Auto Review thất bại hoặc không chắc chắn (Fail / Uncertain)
     */
    public function onAutoReviewFailed(Question $question, string $reason): void
    {
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $activeTickets = ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->get();

        foreach ($activeTickets as $ticket) {
            $ticket->transitionTo(ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED);
        }
    }

    /**
     * Lifecycle Hook: Tự động gỡ công khai câu hỏi sau 7 ngày không đính chính (Auto Private)
     */
    public function autoPrivatizeCase(Question $question, $tickets, int $days, \Carbon\CarbonInterface $now): void
    {
        $earliestReport = $tickets->sortBy('created_at')->first();
        $author = $question->user ?? $question->quiz?->user;

        DB::transaction(function () use ($question, $tickets, $now) {
            $snapshotService = app(QuestionSnapshotService::class);
            $bankSnapshot = $snapshotService->findBankSnapshotByOriginId($question->id);

            if ($bankSnapshot && $bankSnapshot->is_public) {
                $bankSnapshot->update(['is_public' => false]);
            }
            if ($question->is_public) {
                $question->update(['is_public' => false]);
            }

            foreach ($tickets as $t) {
                $t->update(['auto_privatized_at' => $now]);
            }
        });

        if ($author && $earliestReport) {
            try {
                $author->notify(new QuestionModerated($question, 'auto_privatized', $earliestReport->reason, $earliestReport->description));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo auto_privatized: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lifecycle Hook: Gửi cảnh báo / nhắc nhở cho tác giả ở mốc Day 3 và Day 5
     */
    public function sendLifecycleNotification(Question $question, $tickets, string $type, int $days, \Carbon\CarbonInterface $now): void
    {
        $earliestReport = $tickets->sortBy('created_at')->first();
        $author = $question->user ?? $question->quiz?->user;
        $timestampColumn = $type === 'warning' ? 'warning_sent_at' : 'reminder_sent_at';

        foreach ($tickets as $t) {
            $t->update([$timestampColumn => $now]);
        }

        if ($author && $earliestReport) {
            try {
                $author->notify(new QuestionModerated($question, $type, $earliestReport->reason, $earliestReport->description));
            } catch (\Throwable $e) {
                Log::warning("Không thể gửi thông báo {$type}: " . $e->getMessage());
            }
        }
    }

    /**
     * Lấy danh sách báo cáo vi phạm kèm KPI Stats và Case aggregation chuẩn từ Backend
     */
    public function getReportsIndex(array $filters = []): array
    {
        $query = ReportTicket::with([
            'user:id,name,email,avatar',
            'resolver:id,name,email,avatar',
            'question' => function ($q) {
                $q->withTrashed()->with([
                    'user:id,name,email,avatar',
                    'subject:id,name',
                    'grade:id,name',
                    'educationLevel:id,name',
                    'answers',
                    'quizzes:id,title,is_public,status',
                    'quiz:id,title,is_public,status',
                    'latestReviewRequest',
                    'pendingReviewRequest',
                ]);
            },
        ])->latest();

        if (!empty($filters['question_id'])) {
            $query->where('question_id', $filters['question_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $cleanKeyword = ltrim($search, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            // BƯỚC 1: Xác định danh sách question_id phù hợp với từ khóa search
            $matchingQuestionIds = ReportTicket::where(function ($q) use ($search, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('question_id', $numericId);
                }
                $q->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('question', function ($qq) use ($search, $numericId) {
                      $qq->where('content', 'like', "%{$search}%");
                      if ($numericId !== null) {
                          $qq->orWhere('id', $numericId);
                      }
                  });
            })->pluck('question_id')->unique()->all();

            if (empty($matchingQuestionIds)) {
                return [
                    'cases' => [],
                    'reports' => collect([]),
                    'stats' => $this->calculateCasesStats([], collect([])),
                ];
            }

            // BƯỚC 2: Load TOÀN BỘ active và related tickets của các câu hỏi này để Case không bị thiếu ticket
            $query->whereIn('question_id', $matchingQuestionIds);
        }

        $allTickets = $query->get();

        // Gom nhóm theo Question Case và tính toán thuộc tính domain tại Backend
        $grouped = $allTickets->groupBy('question_id');
        $allCases = [];

        foreach ($grouped as $qId => $tickets) {
            $allCases[] = $this->buildSingleReportCase((int) $qId, $tickets);
        }

        // Tính toán KPI stats tập trung
        $stats = $this->calculateCasesStats($allCases, $allTickets);

        // Lọc danh sách Case theo giai đoạn hoặc trạng thái nếu có filter
        $statusFilter = $filters['status'] ?? $filters['stage'] ?? null;
        $filteredCases = $allCases;

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $filteredCases = array_values(array_filter($allCases, function ($c) use ($statusFilter) {
                // 3 Nhóm Workflow Stage chính
                if ($statusFilter === 'needs_admin' || $statusFilter === 'needs_admin_review' || $statusFilter === 'admin_review_required') {
                    return $c['workflow_stage'] === ReportTicket::STAGE_NEEDS_ADMIN;
                }
                if ($statusFilter === 'processing' || $statusFilter === 'in_progress') {
                    return $c['workflow_stage'] === ReportTicket::STAGE_PROCESSING;
                }
                if ($statusFilter === 'completed') {
                    return $c['workflow_stage'] === ReportTicket::STAGE_COMPLETED;
                }

                // Granular status filters (tương thích ngược)
                if ($statusFilter === 'author_updated') {
                    return $c['case_status'] === ReportTicket::STATUS_AUTHOR_UPDATED;
                }
                if ($statusFilter === 'pending') {
                    return $c['case_status'] === ReportTicket::STATUS_PENDING;
                }
                if ($statusFilter === 'auto_privatized') {
                    return $c['is_auto_privatized'];
                }
                if ($statusFilter === 'auto_resolved') {
                    return $c['is_auto_resolved'];
                }
                if ($statusFilter === 'resolved') {
                    return $c['case_status'] === ReportTicket::STATUS_RESOLVED;
                }
                if ($statusFilter === 'dismissed') {
                    return $c['case_status'] === ReportTicket::STATUS_DISMISSED;
                }
                return $c['case_status'] === $statusFilter || $c['workflow_stage'] === $statusFilter;
            }));
        }

        // Sắp xếp: Ưu tiên Case Cần Admin (1) -> Đang xử lý (2-3) -> Đã hoàn tất (4)
        usort($filteredCases, function ($a, $b) {
            if ($a['priority_level'] !== $b['priority_level']) {
                return $a['priority_level'] <=> $b['priority_level'];
            }
            return strcmp($b['latest_report_at'], $a['latest_report_at']);
        });

        // Phân trang Server-side theo Question / Case (10 câu hỏi / trang)
        $totalCases = count($filteredCases);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $perPage = isset($filters['per_page']) ? min(max((int) $filters['per_page'], 1), 100) : 10;
        $lastPage = max((int) ceil($totalCases / $perPage), 1);

        $pagedCases = array_slice($filteredCases, ($page - 1) * $perPage, $perPage);
        $pagedQuestionIds = collect($pagedCases)->pluck('question_id')->all();
        $pagedTickets = $allTickets->filter(fn($t) => in_array($t->question_id, $pagedQuestionIds, true))->values();

        return [
            'cases' => $pagedCases,
            'all_cases' => $filteredCases,
            'reports' => $pagedTickets,
            'all_reports' => $allTickets,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $totalCases,
                'from' => $totalCases > 0 ? ($page - 1) * $perPage + 1 : 0,
                'to' => $totalCases > 0 ? min($page * $perPage, $totalCases) : 0,
            ],
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $totalCases,
        ];
    }

    /**
     * Tổng hợp và tính toán toàn bộ nghiệp vụ của một Question Report Case
     */
    public function buildSingleReportCase(int $questionId, $tickets): array
    {
        $earliestTicket = $tickets->sortBy('created_at')->first();
        $latestTicket = $tickets->sortByDesc('created_at')->first();
        $question = $earliestTicket?->question;

        $workflowStage = ReportTicket::deriveCaseStage($tickets);
        $caseStatus = ReportTicket::deriveCaseStatus($tickets);

        $hasAdminReview = $tickets->contains(fn($t) => $t->status === ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED);
        $hasAuthorUpdated = $tickets->contains(fn($t) => $t->status === ReportTicket::STATUS_AUTHOR_UPDATED || !empty($t->has_author_updated));
        $hasPending = $tickets->contains(fn($t) => $t->status === ReportTicket::STATUS_PENDING);
        $isAutoPrivatized = $tickets->contains(fn($t) => $t->auto_privatized_at !== null);

        $activeTickets = $tickets->filter(fn($t) => in_array($t->status, ReportTicket::ACTIVE_STATUSES, true));
        $allResolved = $tickets->isNotEmpty() && $tickets->every(fn($t) => $t->status === ReportTicket::STATUS_RESOLVED);

        $isAutoResolved = $allResolved && ($tickets->contains(fn($t) => $t->resolution_source === ReportTicket::RESOLUTION_SOURCE_AUTO_REVIEW)
            || !empty($question?->latestReviewRequest?->snapshot_metadata['auto_approved']));

        // Quyết định mức độ ưu tiên và quyền hành động của Admin
        $needsAdminAction = ($workflowStage === ReportTicket::STAGE_NEEDS_ADMIN);
        $priority = $hasAdminReview ? 'high' : ($activeTickets->isNotEmpty() ? 'normal' : 'low');
        $priorityLevel = match($workflowStage) {
            ReportTicket::STAGE_NEEDS_ADMIN => 1,
            ReportTicket::STAGE_PROCESSING => ($hasAuthorUpdated ? 2 : 3),
            ReportTicket::STAGE_COMPLETED => 4,
            default => 5,
        };

        // Thống kê phân bố lý do báo cáo
        $reasonsCount = [];
        foreach ($tickets as $t) {
            $reasonsCount[$t->reason] = ($reasonsCount[$t->reason] ?? 0) + 1;
        }

        // Thông tin xử lý giải quyết từ ticket
        $resolvedTicket = $tickets->first(fn($t) => $t->resolved_at !== null) ?? $latestTicket;

        // Trích xuất thông tin bản duyệt mới nhất
        $latestReq = $question?->latestReviewRequest;
        $latestReqFormatted = null;
        if ($latestReq) {
            $meta = $latestReq->snapshot_metadata ?? [];
            $latestReqFormatted = [
                'id' => $latestReq->id,
                'revision_number' => $latestReq->revision_number ?? 1,
                'status' => $latestReq->status,
                'is_priority' => (bool) $latestReq->is_priority,
                'is_auto_approved' => !empty($meta['auto_approved']),
                'auto_approved_at' => $meta['auto_approved_at'] ?? null,
                'auto_review_failed' => !empty($meta['auto_review_failed']),
                'auto_review_reason' => $meta['auto_review_reason'] ?? null,
                'updated_at' => $latestReq->updated_at?->toIso8601String(),
            ];
        }

        $now = now();
        $earliestCreatedAt = $earliestTicket?->created_at ?? $now;
        $daysOpen = (int) round($earliestCreatedAt->diffInDays($now, false));

        return [
            'question_id' => $questionId,
            'workflow_stage' => $workflowStage,
            'case_status' => $caseStatus,
            'priority' => $priority,
            'priority_level' => $priorityLevel,
            'reports_count' => $tickets->count(),
            'active_reports_count' => $activeTickets->count(),
            'has_author_updated' => $hasAuthorUpdated,
            'is_auto_privatized' => $isAutoPrivatized,
            'is_auto_resolved' => $isAutoResolved,
            'is_question_public' => (bool) ($question?->is_public ?? false),
            'is_question_deleted' => (bool) ($question?->trashed() ?? false),
            'needs_admin_action' => $needsAdminAction,
            'resolution_source' => $resolvedTicket?->resolution_source ?? ($isAutoPrivatized ? ReportTicket::RESOLUTION_SOURCE_SYSTEM : null),
            'resolution_action' => $resolvedTicket?->resolution_action ?? ($isAutoPrivatized ? 'auto_privatized' : null),
            'resolved_at' => $resolvedTicket?->resolved_at?->toIso8601String() ?? ($isAutoPrivatized && $earliestTicket?->auto_privatized_at ? $earliestTicket->auto_privatized_at->toIso8601String() : null),
            'resolved_by' => $resolvedTicket?->resolved_by,
            'resolver' => $resolvedTicket?->resolver,
            'resolution_note' => $resolvedTicket?->resolution_note,
            'earliest_report_at' => $earliestTicket?->created_at?->toIso8601String(),
            'latest_report_at' => $latestTicket?->created_at?->toIso8601String(),
            'days_open' => $daysOpen,
            'reasons_count' => $reasonsCount,
            'question' => $question,
            'latest_review_request' => $latestReqFormatted,
            'tickets' => $tickets->values(),
        ];
    }

    /**
     * Tính toán tổng quan KPI Stats từ toàn bộ các Case và Tickets
     */
    public function calculateCasesStats(array $allCases, $allTickets): array
    {
        $needsAdminCases = 0;
        $processingCases = 0;
        $completedCases = 0;

        $adminReviewCases = 0;
        $authorUpdatedCases = 0;
        $pendingCases = 0;
        $autoPrivatizedCases = 0;
        $autoResolvedCases = 0;
        $resolvedCases = 0;
        $dismissedCases = 0;

        foreach ($allCases as $case) {
            $stage = $case['workflow_stage'] ?? null;
            if ($stage === ReportTicket::STAGE_NEEDS_ADMIN) {
                $needsAdminCases++;
            } elseif ($stage === ReportTicket::STAGE_PROCESSING) {
                $processingCases++;
            } elseif ($stage === ReportTicket::STAGE_COMPLETED) {
                $completedCases++;
            }

            $st = $case['case_status'];
            if ($st === ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED) {
                $adminReviewCases++;
            } elseif ($st === ReportTicket::STATUS_AUTHOR_UPDATED) {
                $authorUpdatedCases++;
            } elseif ($st === ReportTicket::STATUS_PENDING) {
                $pendingCases++;
            } elseif ($st === ReportTicket::STATUS_RESOLVED) {
                $resolvedCases++;
            } elseif ($st === ReportTicket::STATUS_DISMISSED) {
                $dismissedCases++;
            }

            if (!empty($case['is_auto_privatized'])) {
                $autoPrivatizedCases++;
            }
            if (!empty($case['is_auto_resolved'])) {
                $autoResolvedCases++;
            }
        }

        return [
            'total' => $allTickets->count(),
            'total_cases' => count($allCases),

            // 3 Nhóm Workflow Stage chính
            'needs_admin' => $needsAdminCases,
            'needs_admin_cases' => $needsAdminCases,
            'processing' => $processingCases,
            'processing_cases' => $processingCases,
            'completed' => $completedCases,
            'completed_cases' => $completedCases,

            // Detailed Granular counts (tương thích ngược)
            'needs_admin_review' => $needsAdminCases,
            'admin_review_required' => $adminReviewCases,
            'admin_review_required_cases' => $adminReviewCases,
            'author_updated' => $authorUpdatedCases,
            'author_updated_cases' => $authorUpdatedCases,
            'pending' => $pendingCases,
            'pending_cases' => $pendingCases,
            'auto_privatized' => $autoPrivatizedCases,
            'auto_privatized_cases' => $autoPrivatizedCases,
            'auto_resolved' => $autoResolvedCases,
            'auto_resolved_cases' => $autoResolvedCases,
            'resolved' => $resolvedCases,
            'resolved_cases' => $resolvedCases,
            'dismissed' => $dismissedCases,
            'dismissed_cases' => $dismissedCases,
            'exception_cases_count' => $needsAdminCases,
            'questions_count' => count($allCases),

            // Ticket-based counts
            'admin_review_required_tickets' => $allTickets->where('status', ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED)->count(),
            'author_updated_tickets' => $allTickets->where('status', ReportTicket::STATUS_AUTHOR_UPDATED)->count(),
            'pending_tickets' => $allTickets->where('status', ReportTicket::STATUS_PENDING)->count(),
            'auto_privatized_tickets' => $allTickets->whereNotNull('auto_privatized_at')->count(),
            'resolved_tickets' => $allTickets->where('status', ReportTicket::STATUS_RESOLVED)->count(),
            'dismissed_tickets' => $allTickets->where('status', ReportTicket::STATUS_DISMISSED)->count(),
        ];
    }

    /**
     * Thống kê số lượng báo cáo câu hỏi chờ xử lý
     */
    public function getPendingCount(): array
    {
        $questionPending = ReportTicket::whereIn('status', ReportTicket::ACTIVE_STATUSES)->whereNull('auto_privatized_at')->count();
        $uniqueQuestions = ReportTicket::whereIn('status', ReportTicket::ACTIVE_STATUSES)->whereNull('auto_privatized_at')->distinct('question_id')->count('question_id');

        return [
            'count' => $questionPending,
            'question_pending' => $questionPending,
            'unique_questions_pending' => $uniqueQuestions,
        ];
    }

    /**
     * Lấy lịch sử báo cáo của chính người dùng (Reporter) kèm thông điệp kết quả thân thiện
     */
    public function getUserReports(User $user, array $filters = []): array
    {
        $query = ReportTicket::where('user_id', $user->id)
            ->with([
                'question' => function ($q) {
                    $q->withTrashed()->with([
                        'user:id,name,email,avatar',
                        'subject:id,name',
                        'grade:id,name',
                        'educationLevel:id,name',
                        'answers',
                    ]);
                },
            ])
            ->latest();

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $st = $filters['status'];
            if ($st === 'in_progress') {
                $query->whereIn('status', ReportTicket::ACTIVE_STATUSES)->whereNull('auto_privatized_at');
            } elseif ($st === 'completed') {
                $query->where(function ($q) {
                    $q->whereIn('status', ReportTicket::TERMINAL_STATUSES)
                      ->orWhereNotNull('auto_privatized_at');
                });
            } elseif ($st === 'resolved') {
                $query->where('status', ReportTicket::STATUS_RESOLVED);
            } elseif ($st === 'dismissed') {
                $query->where('status', ReportTicket::STATUS_DISMISSED);
            } else {
                $query->where('status', $st);
            }
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $cleanKeyword = ltrim($search, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($search, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('question_id', $numericId)
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('question', function ($qq) use ($search, $numericId) {
                          $qq->where('content', 'like', "%{$search}%")
                             ->orWhere('id', $numericId);
                      });
                } else {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('question', function ($qq) use ($search) {
                          $qq->where('content', 'like', "%{$search}%");
                      });
                }
            });
        }

        $allUserTickets = ReportTicket::where('user_id', $user->id)->get();
        $inProgressCount = $allUserTickets->filter(fn($t) => in_array($t->status, ReportTicket::ACTIVE_STATUSES, true) && empty($t->auto_privatized_at))->count();
        $completedCount = $allUserTickets->filter(fn($t) => in_array($t->status, ReportTicket::TERMINAL_STATUSES, true) || !empty($t->auto_privatized_at))->count();
        $resolvedCount = $allUserTickets->where('status', ReportTicket::STATUS_RESOLVED)->count();
        $dismissedCount = $allUserTickets->where('status', ReportTicket::STATUS_DISMISSED)->count();

        $stats = [
            'total' => $allUserTickets->count(),
            'in_progress' => $inProgressCount,
            'completed' => $completedCount,
            'resolved' => $resolvedCount,
            'dismissed' => $dismissedCount,
        ];

        $tickets = $query->get()->map(function ($ticket) {
            $ticketArray = $ticket->toArray();
            $ticketArray['resolution_message'] = $this->formatReporterResolutionMessage($ticket);
            $ticketArray['status_label'] = $this->formatReporterStatusLabel($ticket);
            return $ticketArray;
        });

        return [
            'reports' => $tickets,
            'stats' => $stats,
        ];
    }

    /**
     * Tạo thông điệp kết quả xử lý có ý nghĩa cho Reporter
     */
    public function formatReporterResolutionMessage(ReportTicket $ticket): string
    {
        if (!empty($ticket->auto_privatized_at)) {
            return 'Câu hỏi đã được chuyển sang chế độ riêng tư sau thời gian xử lý do tác giả không cập nhật nội dung đính chính.';
        }

        if (in_array($ticket->status, ReportTicket::ACTIVE_STATUSES, true)) {
            if ($ticket->status === ReportTicket::STATUS_AUTHOR_UPDATED || !empty($ticket->has_author_updated)) {
                return 'Tác giả đã cập nhật nội dung đính chính. Hệ thống đang tiến hành kiểm định an toàn.';
            }
            if ($ticket->status === ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED) {
                return 'Báo cáo đã được chuyển tới Ban Quản Trị để thẩm định và xử lý trực tiếp.';
            }
            return 'Báo cáo đã được tiếp nhận và chuyển đến Tác giả câu hỏi để xem xét đính chính.';
        }

        if ($ticket->status === ReportTicket::STATUS_RESOLVED) {
            $action = $ticket->resolution_action;
            if ($action === 'keep') {
                return 'Câu hỏi đã được Ban Quản Trị kiểm tra và xác nhận nội dung đạt chuẩn, được giữ nguyên.';
            }
            if ($action === 'hide') {
                return 'Phản ánh chính xác. Câu hỏi vi phạm đã được gỡ khỏi Ngân hàng đề thi công khai.';
            }
            if ($action === 'delete') {
                return 'Phản ánh chính xác. Câu hỏi vi phạm nghiêm trọng đã bị xóa khỏi hệ thống.';
            }
            if ($action === 'approved' || $ticket->resolution_source === ReportTicket::RESOLUTION_SOURCE_AUTO_REVIEW) {
                return 'Tác giả đã chỉnh sửa và cập nhật đáp án / nội dung câu hỏi hoàn tất.';
            }
            return 'Báo cáo đã được xử lý và khép lại thành công.';
        }

        if ($ticket->status === ReportTicket::STATUS_DISMISSED) {
            return 'Báo cáo đã được xem xét và bỏ qua do nội dung câu hỏi không có sai sót hoặc thông tin phản ánh chưa đủ căn cứ.';
        }

        return 'Báo cáo đã được xử lý.';
    }

    /**
     * Nhãn trạng thái thân thiện cho Reporter
     */
    public function formatReporterStatusLabel(ReportTicket $ticket): string
    {
        if (!empty($ticket->auto_privatized_at)) {
            return 'Đã có kết quả';
        }
        if (in_array($ticket->status, ReportTicket::ACTIVE_STATUSES, true)) {
            if ($ticket->status === ReportTicket::STATUS_AUTHOR_UPDATED || !empty($ticket->has_author_updated)) {
                return 'Tác giả đã sửa';
            }
            if ($ticket->status === ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED) {
                return 'Đang thẩm định';
            }
            return 'Đang xử lý';
        }
        if ($ticket->status === ReportTicket::STATUS_RESOLVED) {
            return 'Đã giải quyết';
        }
        if ($ticket->status === ReportTicket::STATUS_DISMISSED) {
            return 'Đã bỏ qua';
        }
        return 'Hoàn tất';
    }
}
