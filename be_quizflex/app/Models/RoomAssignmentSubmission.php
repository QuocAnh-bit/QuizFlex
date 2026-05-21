<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'user_id',
        'attempt_no',
        'status',
        'score',
        'correct_count',
        'wrong_count',
        'total_questions',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(RoomAssignment::class, 'assignment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(RoomAssignmentAnswer::class, 'submission_id');
    }
}