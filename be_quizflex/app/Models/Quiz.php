<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'tag',
        'difficulty',
        'status',
        'is_public',
        'room_code',
        'time_limit_seconds',
        'cover',
        'icon',
        'badge',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'time_limit_seconds' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
