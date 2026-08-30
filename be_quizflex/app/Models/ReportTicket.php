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
        'reminder_sent_at',
        'warning_sent_at',
        'auto_privatized_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
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
}