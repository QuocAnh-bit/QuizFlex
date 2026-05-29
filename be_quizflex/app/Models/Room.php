<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'host_id',
        'quiz_id',
        'name',
        'description',
        'type',
        'code',
        'status',
        'max_players',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

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
}
