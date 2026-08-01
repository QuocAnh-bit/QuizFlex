<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAssignment extends Model
{
    use HasFactory;

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
          'shuffle_questions',
    'shuffle_answers',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'deadline_at' => 'datetime',
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',

          'shuffle_questions' => 'boolean',
    'shuffle_answers' => 'boolean',
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

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'assignment_id');
    }
}
