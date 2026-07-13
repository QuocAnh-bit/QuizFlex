<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTicket extends Model
{
    use HasFactory;

    // Khai báo tên bảng (tùy chọn, vì Laravel tự hiểu số nhiều nhưng viết vào cho chắc chắn)
    protected $table = 'report_tickets';

    // Các trường cho phép thêm/sửa hàng loạt (Mass Assignment)
    protected $fillable = [
        'user_id',
        'quiz_id',
        'reason',
        'description',
        'status',
    ];

    /**
     * Mối quan hệ: Một lượt báo cáo thuộc về một Người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ: Một lượt báo cáo thuộc về một bài Quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}