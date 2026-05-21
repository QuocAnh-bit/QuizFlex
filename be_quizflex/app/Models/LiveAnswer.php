<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveAnswer extends Model
{
    protected $fillable = [
        'session_id',
        'participant_id',
        'user_id',
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

    public function session()
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function participant()
    {
        return $this->belongsTo(LiveParticipant::class, 'participant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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