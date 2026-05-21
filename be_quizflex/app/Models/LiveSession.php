<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSession extends Model
{
    protected $fillable = [
        'room_id',
        'quiz_id',
        'host_id',
        'assignment_id',
        'code',
        'status',
        'current_question_id',
        'current_question_index',
        'question_duration_sec',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function assignment()
    {
        return $this->belongsTo(RoomAssignment::class, 'assignment_id');
    }

    public function currentQuestion()
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function participants()
    {
        return $this->hasMany(LiveParticipant::class, 'session_id');
    }

    public function answers()
    {
        return $this->hasMany(LiveAnswer::class, 'session_id');
    }
}