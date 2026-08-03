<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar',
        'ai_quota_remaining', 'vip_expires_at', 'trial_used_at',
        'plan', 'plan_started_at', 'plan_expires_at', 'is_main_admin',
        'is_locked', 'locked_at', 'locked_reason', 'locked_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'vip_expires_at'    => 'datetime',
            'trial_used_at'     => 'datetime',
            'plan_started_at'   => 'datetime',
            'plan_expires_at'   => 'datetime',
            'is_main_admin'     => 'boolean',
            'is_locked'         => 'boolean',
            'locked_at'         => 'datetime',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function quizzes()         { return $this->hasMany(Quiz::class); }
    public function attempts()        { return $this->hasMany(QuizAttempt::class); }
    public function ownedRooms()      { return $this->hasMany(Room::class, 'owner_id'); }
    public function hostedRooms()     { return $this->hasMany(Room::class, 'host_id'); }
    public function roomMemberships() { return $this->hasMany(RoomMember::class); }
    public function payments()        { return $this->hasMany(Payment::class); }
    public function lockedBy()        { return $this->belongsTo(User::class, 'locked_by'); }
    public function unlockRequests()  { return $this->hasMany(UnlockRequest::class); }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Role hệ thống: 'admin' | 'free' | 'plus' | 'pro' | 'ultra' */
    public function getRole(): string
    {
        $role = strtolower(trim((string) ($this->role ?? '')));

        if ($role === '' || in_array($role, ['user', 'guest', 'member', 'basic', 'default'], true)) {
            return 'free';
        }

        if ($role === 'administrator') {
            return 'admin';
        }

        return in_array($role, ['admin', 'free', 'plus', 'pro', 'ultra'], true)
            ? $role
            : 'free';
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === 'admin';
    }

    public function isMainAdmin(): bool
    {
        return $this->isAdmin() && (
            strtolower(trim($this->email ?? '')) === 'vip@gmail.com'
            || (bool) $this->is_main_admin
        );
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /**
     * Plan hiện tại (tính toán động, tự reset nếu hết hạn).
     * Trả về: 'free' | 'plus' | 'pro' | 'ultra'
     */
    public function getActivePlan(): string
    {
        $roleStr = strtolower(trim((string) ($this->role ?? '')));
        $planStr = strtolower(trim((string) ($this->plan ?? '')));

        if (in_array($roleStr, ['admin', 'administrator'], true) || in_array($planStr, ['admin', 'administrator'], true)) {
            return 'admin';
        }

        // Tìm tier cao nhất giữa role và plan
        $candidateRole = 'free';
        foreach ([$roleStr, $planStr] as $val) {
            if (in_array($val, ['ultra', 'pro', 'plus'], true)) {
                $candidateRole = $val;
                break;
            }
        }

        if ($candidateRole === 'free') {
            return 'free';
        }

        // Kiểm tra hạn VIP theo vip_expires_at hoặc plan_expires_at
        $expiry = $this->vip_expires_at ?? $this->plan_expires_at;

        if ($expiry && \Carbon\Carbon::parse($expiry)->isFuture()) {
            return $candidateRole;
        }

        return 'free';
    }

    /**
     * Giữ tương thích ngược với code cũ dùng getSubscriptionTier().
     */
    public function getSubscriptionTier(): string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }

        return $this->getActivePlan();
    }

    // ─── JWT ─────────────────────────────────────────────────────────────────

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['role' => $this->getRole()];
    }
}
