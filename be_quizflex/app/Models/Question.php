<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'content',
        'image_url',
        'type',
        'difficulty',
        'education_level_id',
        'grade_id',
        'subject_id',
        'topic_name',
        'is_public',
        'order',
        'points',
        'question',
        'correct_answer'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'order' => 'integer',
        'points' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->withPivot(['order', 'points'])
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function answers()
    {
        return $this->hasMany(Answer::class)->orderBy('order');
    }
}
