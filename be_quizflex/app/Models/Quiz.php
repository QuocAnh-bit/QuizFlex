<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'education_level_id',
        'grade_id',
        'subject_id',
        'topic_name',
        'tag',
        'difficulty',
        'status',
        'is_public',
        'room_code',
        'time_limit_seconds',
        'cover',
        'icon',
        'badge',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'time_limit_seconds' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->withPivot(['order', 'points'])
            ->withTimestamps()
            ->orderBy('quiz_questions.order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
