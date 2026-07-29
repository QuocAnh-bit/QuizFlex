<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'ai_quota_remaining', 'vip_expires_at', 'trial_used_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function hostedRooms()
    {
        return $this->hasMany(Room::class, 'host_id');
    }

    public function roomMemberships()
    {
        return $this->hasMany(RoomMember::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'vip_expires_at' => 'datetime',
            'trial_used_at' => 'datetime',
        ];
    }

    /**
     * Lấy cấp độ subscription thực tế của người dùng (tính toán động theo ngày hết hạn).
     */
    public function getSubscriptionTier(): string
    {
        if (strtolower($this->role) === 'admin') {
            return 'admin';
        }

        if ($this->vip_expires_at && \Carbon\Carbon::parse($this->vip_expires_at)->isFuture()) {
            $role = strtolower($this->role);
            return $role === 'free' ? 'plus' : $role;
        }

        // Hết hạn VIP: tự động cập nhật role cột trong CSDL về FREE
        if (in_array(strtoupper($this->role), ['PLUS', 'PRO', 'ULTRA'])) {
            $this->role = 'FREE';
            $this->save();
        }

        return 'free';
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }
}
