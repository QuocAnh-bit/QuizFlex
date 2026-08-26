<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumChunk extends Model
{
    protected $fillable = [
        'unit_id',

        'chunk_index',

        'content',

        'embedding_text',

        'estimated_tokens',

        'content_hash',

        'embedding_model',

        'embedding_status',

        'qdrant_point_id',

        'embedding_error',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'estimated_tokens' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(
            CurriculumUnit::class,
            'unit_id'
        );
    }
}
