<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'content',
        'image_url',
        'type',
        'order',
        'points',
    ];

    protected $casts = [
        'order' => 'integer',
        'points' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->orderBy('order');
    }

    public function roomAssignmentAnswers()
{
    return $this->hasMany(RoomAssignmentAnswer::class);
}

public function liveAnswers()
{
    return $this->hasMany(LiveAnswer::class);
}
}
