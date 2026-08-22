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
        'origin_question_id',
        'fingerprint',
        'content',
        'image_url',
        'type',
        'difficulty',
        'education_level_id',
        'grade_id',
        'subject_id',
        'topic_name',
        'is_public',
        'bank_submission_status',
        'bank_submission_note',
        'bank_submission_at',
        'order',
        'points',
        'question',
        'correct_answer'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'bank_submission_at' => 'datetime',
        'order' => 'integer',
        'points' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (Question $question) {
            if (empty($question->fingerprint) && !empty($question->content)) {
                $snapshotService = app(\App\Services\QuestionSnapshotService::class);
                $question->fingerprint = $snapshotService->computeFingerprint($question);
            }
        });
    }

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

    public function originQuestion()
    {
        return $this->belongsTo(Question::class, 'origin_question_id');
    }

    public function snapshots()
    {
        return $this->hasMany(Question::class, 'origin_question_id');
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

    public function reviewRequests()
    {
        return $this->hasMany(QuestionReviewRequest::class, 'question_id')->orderBy('revision_number', 'desc');
    }

    public function latestReviewRequest()
    {
        return $this->hasOne(QuestionReviewRequest::class, 'question_id')->latestOfMany();
    }

    public function pendingReviewRequest()
    {
        return $this->hasOne(QuestionReviewRequest::class, 'question_id')->where('status', 'pending');
    }
}
