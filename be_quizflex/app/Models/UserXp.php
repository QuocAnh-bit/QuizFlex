<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserXp extends Model
{
    protected $table = 'user_xp'; // chỉ định đúng tên bảng

    protected $fillable = ['user_id', 'xp', 'level'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
