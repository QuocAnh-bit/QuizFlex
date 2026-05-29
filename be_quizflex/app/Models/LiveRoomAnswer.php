<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveRoomAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_room_id',
        'user_id',
        'question_id',
        'answer_id',
        'is_correct',
        'score_awarded',
        'answered_at',
        'response_time_ms',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'score_awarded' => 'integer',
        'answered_at' => 'datetime',
        'response_time_ms' => 'integer',
    ];

    public function liveRoom()
    {
        return $this->belongsTo(LiveRoom::class);
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
