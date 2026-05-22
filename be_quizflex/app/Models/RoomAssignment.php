<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAssignment extends Model
{
    protected $fillable = [
        'room_id',
        'quiz_id',
        'assigned_by',
        'title',
        'description',
        'starts_at',
        'deadline_at',
        'duration_minutes',
        'max_attempts',
        'show_result_mode',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function submissions()
    {
        return $this->hasMany(RoomAssignmentSubmission::class, 'assignment_id');
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class, 'assignment_id');
    }
}
