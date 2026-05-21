<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAssignmentAnswer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'answer_id',
        'selected_answer_ids',
        'is_correct',
        'score',
        'response_time_ms',
        'answered_at',
    ];

    protected $casts = [
        'selected_answer_ids' => 'array',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(RoomAssignmentSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}