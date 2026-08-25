<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizReviewRequest extends Model
{
    use HasFactory;

    protected $table = 'quiz_review_requests';

    protected $fillable = [
        'quiz_id',
        'user_id',
        'revision_number',
        'status',
        'request_note',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'snapshot_title',
        'snapshot_description',
        'snapshot_education_level_id',
        'snapshot_grade_id',
        'snapshot_subject_id',
        'snapshot_topic_name',
        'snapshot_time_limit_minutes',
        'snapshot_shuffle_questions',
        'snapshot_cover',
        'snapshot_questions',
        'snapshot_metadata',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'reviewed_at' => 'datetime',
        'snapshot_shuffle_questions' => 'boolean',
        'snapshot_questions' => 'array',
        'snapshot_metadata' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class)->withTrashed();
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
