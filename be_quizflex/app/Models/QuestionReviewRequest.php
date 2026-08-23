<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionReviewRequest extends Model
{
    use HasFactory;

    protected $table = 'question_review_requests';

    protected $fillable = [
        'question_id',
        'user_id',
        'revision_number',
        'status',
        'review_priority',
        'is_priority',
        'request_note',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'snapshot_content',
        'snapshot_type',
        'snapshot_difficulty',
        'snapshot_education_level_id',
        'snapshot_grade_id',
        'snapshot_subject_id',
        'snapshot_topic_name',
        'snapshot_points',
        'snapshot_image_url',
        'snapshot_answers',
        'snapshot_metadata',
    ];

    protected $casts = [
        'snapshot_answers' => 'array',
        'snapshot_metadata' => 'array',
        'is_priority' => 'boolean',
        'reviewed_at' => 'datetime',
        'revision_number' => 'integer',
        'snapshot_points' => 'integer',
    ];


    public function question()
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class, 'snapshot_education_level_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'snapshot_grade_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'snapshot_subject_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
