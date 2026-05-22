<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJob extends Model
{
    protected $table = 'ai_jobs';

    protected $fillable = [
        'user_id',
        'ai_log_id',
        'uuid',
        'prompt',
        'requested_count',
        'difficulty',
        'language',
        'visibility',
        'questions_generated',
        'status',
        'quiz_id',
        'response_json',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'requested_count' => 'integer',
        'questions_generated' => 'integer',
        'response_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(AiLog::class, 'ai_log_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
