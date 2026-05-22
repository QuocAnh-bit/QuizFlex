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
        'score',
        'total_points',
        'time_spent_seconds',
        'answers_snapshot',
        'status',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_points' => 'integer',
        'time_spent_seconds' => 'integer',
        'answers_snapshot' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
