<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $table = 'ai_logs';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'action_type',
        'file_path',
        'tokens_used',
        'questions_generated',
        'status',
        'error_message',
        'response_json',
    ];

    protected $casts = [
        'response_json' => 'array',
    ];
}