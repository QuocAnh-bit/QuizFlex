<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['education_level_id', 'code', 'name', 'level_number', 'order'];

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_grade');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
