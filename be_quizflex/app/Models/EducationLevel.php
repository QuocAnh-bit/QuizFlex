<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'order'];

    public function grades()
    {
        return $this->hasMany(Grade::class)->orderBy('order');
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
