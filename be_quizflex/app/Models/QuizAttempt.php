<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'room_id',
        'assignment_id',
        'mode',
        'attempt_number',
        'score',
        'total_points',
        'time_spent_seconds',
        'answers_snapshot',
        'question_order',
        'status',
        'started_at',
        'finished_at',
        'submitted_at',
        'xp_earned',
        'violation_count',
        'violation_log',
        'is_locked',
        'locked_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'score' => 'float',
        'total_points' => 'float',
        'time_spent_seconds' => 'integer',
        'answers_snapshot' => 'array',
        'question_order' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'submitted_at' => 'datetime',
        'xp_earned' => 'integer',
        'violation_count' => 'integer',
        'violation_log' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function assignment()
    {
        return $this->belongsTo(RoomAssignment::class, 'assignment_id');
    }

    public function evaluation()
    {
        return $this->hasOne(RoomSubmissionEvaluation::class, 'submission_id');
    }
}
