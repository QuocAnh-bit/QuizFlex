<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveParticipant extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'display_name',
        'status',
        'score',
        'correct_count',
        'wrong_count',
        'current_question_index',
        'is_finished',
        'finished_at',
        'rank',
        'is_ready',
        'joined_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'is_finished' => 'boolean',
        'finished_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(LiveAnswer::class, 'participant_id');
    }
}
