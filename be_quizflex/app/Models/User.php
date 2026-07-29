<?php

namespace App\Models;

use Database\Factories\UserFactory;
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
        ];
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function quizzes()    { return $this->hasMany(Quiz::class); }
    public function attempts()   { return $this->hasMany(QuizAttempt::class); }
    public function ownedRooms() { return $this->hasMany(Room::class, 'owner_id'); }
    public function payments()   { return $this->hasMany(Payment::class); }

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

    /**
     * Plan hiện tại (tính toán động, tự reset nếu hết hạn).
     * Trả về: 'free' | 'plus' | 'pro' | 'ultra'
     */
    public function getActivePlan(): string
    {
        $plan = strtolower($this->plan ?? 'free');

        if ($plan === 'free') {
            return 'free';
        }

        if ($this->plan_expires_at && $this->plan_expires_at->isFuture()) {
            return $plan;
        }

        // Hết hạn → reset về free
        $this->plan            = 'free';
        $this->plan_started_at = null;
        $this->plan_expires_at = null;
        $this->save();

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
