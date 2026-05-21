<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'host_id',
        'quiz_id',
        'code',
        'status',
        'max_players',
        'started_at',
        'ended_at',
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function members()
    {
        return $this->hasMany(RoomMember::class);
    }

    public function assignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class);
    }
}