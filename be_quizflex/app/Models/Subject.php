<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'icon', 'category_group', 'order'];

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'subject_grade');
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
