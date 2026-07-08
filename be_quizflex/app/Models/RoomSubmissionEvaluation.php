<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSubmissionEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'submission_id',
        'user_id',
        'evaluator_id',
        'comment',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function submission()
    {
        return $this->belongsTo(QuizAttempt::class, 'submission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
