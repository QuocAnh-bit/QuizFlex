<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumUnit extends Model
{
    protected $fillable = [
        'document_id',

        'type',

        'subject',

        'grade_min',
        'grade_max',

        'education_level',

        'domain',
        'topic',

        'section',
        'subsection',

        'title',
        'author',
        'genre',
        'selection_type',

        'content',

        'learning_outcomes',

        'source_page_start',
        'source_page_end',

        'parser_version',

        'is_verified',
    ];

    protected $casts = [
        'learning_outcomes' => 'array',

        'grade_min' => 'integer',
        'grade_max' => 'integer',

        'source_page_start' => 'integer',
        'source_page_end' => 'integer',

        'is_verified' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            CurriculumDocument::class,
            'document_id'
        );
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(
            CurriculumChunk::class,
            'unit_id'
        );
    }
}
