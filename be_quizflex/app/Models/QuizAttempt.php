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
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'score' => 'integer',
        'total_points' => 'integer',
        'time_spent_seconds' => 'integer',
        'answers_snapshot' => 'array',
        'question_order' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'submitted_at' => 'datetime',
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
}
