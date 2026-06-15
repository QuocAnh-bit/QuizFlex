<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id',
        'quiz_id',
        'code',
        'title',
        'status',
        'current_question_index',
        'question_order',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'current_question_index' => 'integer',
        'question_order' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function players()
    {
        return $this->hasMany(LiveRoomPlayer::class);
    }

    public function answers()
    {
        return $this->hasMany(LiveRoomAnswer::class);
    }
}
