<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTicket extends Model
{
    use HasFactory;

    protected $table = 'report_tickets';

    // -------------------------------------------------------------
    // STATUS CONSTANTS & ENUMS
    // -------------------------------------------------------------
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHOR_UPDATED = 'author_updated';
    public const STATUS_ADMIN_REVIEW_REQUIRED = 'admin_review_required';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_AUTHOR_UPDATED,
        self::STATUS_ADMIN_REVIEW_REQUIRED,
        self::STATUS_RESOLVED,
        self::STATUS_DISMISSED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_AUTHOR_UPDATED,
        self::STATUS_ADMIN_REVIEW_REQUIRED,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_RESOLVED,
        self::STATUS_DISMISSED,
    ];

    // -------------------------------------------------------------
    // WORKFLOW STAGE CONSTANTS (SIMPLIFIED 3-STAGE CASE ARCHITECTURE)
    // -------------------------------------------------------------
    public const STAGE_NEEDS_ADMIN = 'needs_admin';
    public const STAGE_PROCESSING = 'processing';
    public const STAGE_COMPLETED = 'completed';

    public const ALL_STAGES = [
        self::STAGE_NEEDS_ADMIN,
        self::STAGE_PROCESSING,
        self::STAGE_COMPLETED,
    ];

    // -------------------------------------------------------------
    // RESOLUTION SOURCE & ACTION CONSTANTS
    // -------------------------------------------------------------
    public const RESOLUTION_SOURCE_ADMIN = 'admin';
    public const RESOLUTION_SOURCE_AUTO_REVIEW = 'auto_review';
    public const RESOLUTION_SOURCE_AUTHOR_REVISION = 'author_revision';
    public const RESOLUTION_SOURCE_SYSTEM = 'system';

    public const ALL_RESOLUTION_SOURCES = [
        self::RESOLUTION_SOURCE_ADMIN,
        self::RESOLUTION_SOURCE_AUTO_REVIEW,
        self::RESOLUTION_SOURCE_AUTHOR_REVISION,
        self::RESOLUTION_SOURCE_SYSTEM,
    ];

    public const RESOLUTION_ACTION_APPROVED = 'approved';
    public const RESOLUTION_ACTION_KEEP = 'keep';
    public const RESOLUTION_ACTION_HIDE = 'hide';
    public const RESOLUTION_ACTION_DELETE = 'delete';
    public const RESOLUTION_ACTION_DISMISSED = 'dismissed';

    public const ALL_RESOLUTION_ACTIONS = [
        self::RESOLUTION_ACTION_APPROVED,
        self::RESOLUTION_ACTION_KEEP,
        self::RESOLUTION_ACTION_HIDE,
        self::RESOLUTION_ACTION_DELETE,
        self::RESOLUTION_ACTION_DISMISSED,
    ];

    // -------------------------------------------------------------
    // CRITICAL CLASSIFICATION & THRESHOLDS
    // -------------------------------------------------------------
    public const CRITICAL_KEYWORDS = [
        'nhạy cảm',
        'xúc phạm',
        'bản quyền',
        'chính sách',
        'nghiêm trọng',
        'phản động',
        'khiêu dâm',
        'vi phạm pháp luật',
        'lừa đảo',
        'xâm phạm',
        'bạo lực',
    ];

    public const MULTI_REPORT_THRESHOLD = 3;

    // -------------------------------------------------------------
    // STATE TRANSITION RULES MATRIX
    // -------------------------------------------------------------
    public static array $validTransitions = [
        self::STATUS_PENDING => [
            self::STATUS_PENDING,
            self::STATUS_AUTHOR_UPDATED,
            self::STATUS_ADMIN_REVIEW_REQUIRED,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ],
        self::STATUS_AUTHOR_UPDATED => [
            self::STATUS_AUTHOR_UPDATED,
            self::STATUS_ADMIN_REVIEW_REQUIRED,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ],
        self::STATUS_ADMIN_REVIEW_REQUIRED => [
            self::STATUS_AUTHOR_UPDATED, // Cho phép khi tác giả sửa lại bản vá
            self::STATUS_ADMIN_REVIEW_REQUIRED,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ],
        self::STATUS_RESOLVED => [
            // Resolved là Terminal State! Tuyệt đối không cho phép chuyển ngược về pending hoặc author_updated
        ],
        self::STATUS_DISMISSED => [
            // Dismissed là Terminal State! Tuyệt đối không tự động approve hay chuyển về pending
            // Chỉ cho phép mở lại thành admin_review_required nếu có can thiệp thủ công từ Admin
            self::STATUS_ADMIN_REVIEW_REQUIRED,
        ],
    ];

    protected $fillable = [
        'user_id',
        'question_id',
        'reason',
        'description',
        'status',
        'has_author_updated',
        'resolution_source',
        'resolution_action',
        'resolved_at',
        'resolved_by',
        'resolution_note',
        'reminder_sent_at',
        'warning_sent_at',
        'auto_privatized_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'has_author_updated' => 'boolean',
        'resolved_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'warning_sent_at' => 'datetime',
        'auto_privatized_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một lượt báo cáo thuộc về một Người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ: Người xử lý / giải quyết báo cáo
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Mối quan hệ: Một lượt báo cáo thuộc về một Câu hỏi
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id')->withTrashed();
    }

    // -------------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------------
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAuthorUpdated($query)
    {
        return $query->where('status', self::STATUS_AUTHOR_UPDATED);
    }

    public function scopeAdminReviewRequired($query)
    {
        return $query->where('status', self::STATUS_ADMIN_REVIEW_REQUIRED);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    // -------------------------------------------------------------
    // HELPER & DOMAIN METHODS
    // -------------------------------------------------------------
    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return true;
        }

        $allowed = self::$validTransitions[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    public function transitionTo(string $targetStatus): bool
    {
        if (!$this->canTransitionTo($targetStatus)) {
            \Illuminate\Support\Facades\Log::warning("Invalid ReportTicket status transition from {$this->status} to {$targetStatus} for ticket #{$this->id}");
            return false;
        }

        if ($this->status !== $targetStatus) {
            $this->status = $targetStatus;
            $this->save();
        }

        return true;
    }

    /**
     * Đánh dấu ticket đã được giải quyết với đầy đủ metadata
     */
    public function markResolved(
        string $source = self::RESOLUTION_SOURCE_ADMIN,
        ?string $action = self::RESOLUTION_ACTION_APPROVED,
        ?int $resolvedBy = null,
        ?string $note = null
    ): bool {
        if (!$this->canTransitionTo(self::STATUS_RESOLVED)) {
            return false;
        }

        $this->status = self::STATUS_RESOLVED;
        $this->resolution_source = $source;
        $this->resolution_action = $action ?? self::RESOLUTION_ACTION_APPROVED;
        $this->resolved_at = now();
        $this->resolved_by = $resolvedBy;
        if ($note !== null) {
            $this->resolution_note = $note;
        }
        $this->save();

        return true;
    }

    /**
     * Đánh dấu ticket đã bị bác bỏ/bỏ qua với đầy đủ metadata
     */
    public function markDismissed(
        string $source = self::RESOLUTION_SOURCE_ADMIN,
        ?int $resolvedBy = null,
        ?string $note = null
    ): bool {
        if (!$this->canTransitionTo(self::STATUS_DISMISSED)) {
            return false;
        }

        $this->status = self::STATUS_DISMISSED;
        $this->resolution_source = $source;
        $this->resolution_action = self::RESOLUTION_ACTION_DISMISSED;
        $this->resolved_at = now();
        $this->resolved_by = $resolvedBy;
        if ($note !== null) {
            $this->resolution_note = $note;
        }
        $this->save();

        return true;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    public static function isCriticalReason(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }

        $pattern = '/' . implode('|', array_map('preg_quote', self::CRITICAL_KEYWORDS)) . '/ui';
        return (bool) preg_match($pattern, $reason);
    }

    public static function determineInitialStatus(string $reason, int $unresolvedCount): string
    {
        if (self::isCriticalReason($reason)) {
            return self::STATUS_ADMIN_REVIEW_REQUIRED;
        }

        // Nếu đã có >= 2 báo cáo chưa xử lý (thêm report mới này là >= 3)
        if ($unresolvedCount >= (self::MULTI_REPORT_THRESHOLD - 1)) {
            return self::STATUS_ADMIN_REVIEW_REQUIRED;
        }

        return self::STATUS_PENDING;
    }

    /**
     * Derive Case Status từ danh sách tickets của 1 câu hỏi
     */
    public static function deriveCaseStatus($tickets): string
    {
        $ticketsColl = is_array($tickets) ? collect($tickets) : $tickets;

        if ($ticketsColl->isEmpty()) {
            return self::STATUS_PENDING;
        }

        $hasAdminReview = $ticketsColl->contains(fn($t) => data_get($t, 'status') === self::STATUS_ADMIN_REVIEW_REQUIRED);
        if ($hasAdminReview) {
            return self::STATUS_ADMIN_REVIEW_REQUIRED;
        }

        // Nếu tất cả active tickets đã auto-privatized thì case đã hoàn tất
        $hasActiveNonPrivatized = $ticketsColl->contains(function ($t) {
            $st = data_get($t, 'status');
            $isPrivatized = !empty(data_get($t, 'auto_privatized_at'));
            return in_array($st, [self::STATUS_PENDING, self::STATUS_AUTHOR_UPDATED], true) && !$isPrivatized;
        });

        if ($hasActiveNonPrivatized) {
            $hasAuthorUpdated = $ticketsColl->contains(fn($t) => data_get($t, 'status') === self::STATUS_AUTHOR_UPDATED && empty(data_get($t, 'auto_privatized_at')));
            if ($hasAuthorUpdated) {
                return self::STATUS_AUTHOR_UPDATED;
            }

            return self::STATUS_PENDING;
        }

        $allDismissed = $ticketsColl->every(fn($t) => data_get($t, 'status') === self::STATUS_DISMISSED);
        if ($allDismissed) {
            return self::STATUS_DISMISSED;
        }

        return self::STATUS_RESOLVED;
    }

    /**
     * Derive Workflow Stage (needs_admin, processing, completed) từ danh sách tickets của 1 câu hỏi
     */
    public static function deriveCaseStage($tickets): string
    {
        $ticketsColl = is_array($tickets) ? collect($tickets) : $tickets;

        if ($ticketsColl->isEmpty()) {
            return self::STAGE_COMPLETED;
        }

        $hasAdminReview = $ticketsColl->contains(fn($t) => data_get($t, 'status') === self::STATUS_ADMIN_REVIEW_REQUIRED);
        if ($hasAdminReview) {
            return self::STAGE_NEEDS_ADMIN;
        }

        // Active tickets chưa bị auto-privatized thì thuộc STAGE_PROCESSING
        $hasActiveProcessing = $ticketsColl->contains(function ($t) {
            $st = data_get($t, 'status');
            $isPrivatized = !empty(data_get($t, 'auto_privatized_at'));
            return in_array($st, [self::STATUS_PENDING, self::STATUS_AUTHOR_UPDATED], true) && !$isPrivatized;
        });

        if ($hasActiveProcessing) {
            return self::STAGE_PROCESSING;
        }

        return self::STAGE_COMPLETED;
    }

    /**
     * Derive Case DTO / Structure từ Question và danh sách tickets liên quan
     */
    public static function deriveCaseData(Question $question, $tickets = null): array
    {
        $ticketsColl = $tickets !== null
            ? (is_array($tickets) ? collect($tickets) : $tickets)
            : $question->reports;

        $reportsCount = $ticketsColl->count();
        $activeTickets = $ticketsColl->filter(fn($t) => in_array($t->status ?? $t['status'] ?? '', self::ACTIVE_STATUSES, true));
        $activeReportsCount = $activeTickets->count();

        $caseStatus = self::deriveCaseStatus($ticketsColl);
        $workflowStage = self::deriveCaseStage($ticketsColl);

        // Derive priority
        $isCritical = $ticketsColl->contains(function ($t) {
            $reason = $t->reason ?? $t['reason'] ?? '';
            return self::isCriticalReason($reason);
        });
        $priority = $isCritical || $activeReportsCount >= self::MULTI_REPORT_THRESHOLD
            ? 'critical'
            : ($activeReportsCount > 0 ? 'high' : 'normal');

        // Derive author_updated flag
        $authorUpdated = $ticketsColl->contains(fn($t) => !empty($t->has_author_updated) || ($t->status ?? '') === self::STATUS_AUTHOR_UPDATED);

        // Derive auto_privatized flag
        $autoPrivatized = $ticketsColl->contains(fn($t) => !empty($t->auto_privatized_at));

        // Derive resolution metadata from resolved tickets or auto_privatized
        $resolvedTicket = $ticketsColl->first(fn($t) => in_array($t->status ?? '', self::TERMINAL_STATUSES, true) && !empty($t->resolution_source));
        $resolutionSource = $resolvedTicket ? ($resolvedTicket->resolution_source ?? null) : null;
        $resolutionAction = $resolvedTicket ? ($resolvedTicket->resolution_action ?? null) : null;
        $resolvedAt = $resolvedTicket && !empty($resolvedTicket->resolved_at) ? $resolvedTicket->resolved_at : null;
        $resolvedBy = $resolvedTicket ? ($resolvedTicket->resolved_by ?? null) : null;

        if ($autoPrivatized && $workflowStage === self::STAGE_COMPLETED) {
            if (empty($resolutionSource)) {
                $resolutionSource = self::RESOLUTION_SOURCE_SYSTEM;
            }
            if (empty($resolutionAction)) {
                $resolutionAction = 'auto_privatized';
            }
            if (empty($resolvedAt)) {
                $privatizedTicket = $ticketsColl->first(fn($t) => !empty($t->auto_privatized_at));
                $resolvedAt = $privatizedTicket?->auto_privatized_at ?? null;
            }
        }

        $latestTicket = $ticketsColl->sortByDesc('created_at')->first();
        $latestReportAt = $latestTicket ? ($latestTicket->created_at ?? null) : null;

        return [
            'question_id' => $question->id,
            'reports_count' => $reportsCount,
            'active_reports_count' => $activeReportsCount,
            'workflow_stage' => $workflowStage,
            'case_status' => $caseStatus,
            'priority' => $priority,
            'author_updated' => $authorUpdated,
            'auto_privatized' => $autoPrivatized,
            'resolution_source' => $resolutionSource,
            'resolution_action' => $resolutionAction,
            'resolved_at' => $resolvedAt ? (is_string($resolvedAt) ? $resolvedAt : $resolvedAt->toIso8601String()) : null,
            'resolved_by' => $resolvedBy,
            'latest_report_at' => $latestReportAt ? (is_string($latestReportAt) ? $latestReportAt : $latestReportAt->toIso8601String()) : null,
        ];
    }
}