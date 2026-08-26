<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumDocument extends Model
{
    protected $fillable = [
        'subject',
        'title',
        'file_path',
        'publisher',
        'legal_document',
        'curriculum_version',
        'checksum',
        'page_count',
        'status',
        'error_message',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(
            CurriculumUnit::class,
            'document_id'
        );
    }
}
