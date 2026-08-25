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
        'creation_mode',
        'status',
        'review_status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
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
        'reviewed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviewRequests()
    {
        return $this->hasMany(QuizReviewRequest::class)->latest();
    }

    public function latestReviewRequest()
    {
        return $this->hasOne(QuizReviewRequest::class)->latestOfMany();
    }

    public function pendingReviewRequest()
    {
        return $this->hasOne(QuizReviewRequest::class)->where('status', 'pending');
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

    public function scopePublicApproved($query)
    {
        return $query->where('is_public', true)
            ->where('status', 'published')
            ->where('review_status', 'approved');
    }

    public function scopePendingReview($query)
    {
        return $query->where('review_status', 'pending_review');
    }

    public function isManual(): bool
    {
        return ($this->creation_mode ?? 'manual') === 'manual';
    }

    public function isAuto(): bool
    {
        return ($this->creation_mode ?? 'manual') === 'auto';
    }

    public function isPendingReview(): bool
    {
        return $this->review_status === 'pending_review';
    }

    public function isApproved(): bool
    {
        return $this->review_status === 'approved';
    }
}
