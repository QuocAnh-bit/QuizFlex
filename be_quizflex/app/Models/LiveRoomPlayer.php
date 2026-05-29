<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveRoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_room_id',
        'user_id',
        'score',
        'correct_count',
        'current_question_index',
        'joined_at',
        'finished_at',
        'last_answered_at',
        'status',
    ];

    protected $casts = [
        'score' => 'integer',
        'correct_count' => 'integer',
        'current_question_index' => 'integer',
        'joined_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_answered_at' => 'datetime',
    ];

    public function liveRoom()
    {
        return $this->belongsTo(LiveRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
