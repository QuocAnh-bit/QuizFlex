<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_tickets', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại liên kết tới người dùng thực hiện báo cáo
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Khóa ngoại liên kết tới bài Quiz bị báo cáo
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            
            // Phân loại lý do báo cáo (vd: 'Thông tin sai lệch', 'Nội dung nhạy cảm', 'Spam')
            $table->string('reason'); 
            
            // Mô tả chi tiết thêm do người dùng nhập (có thể bỏ trống)
            $table->text('description')->nullable(); 
            
            // Trạng thái xử lý của Admin (mặc định khi mới tạo là đang chờ xử lý - pending)
            $table->enum('status', ['pending', 'author_updated', 'admin_review_required', 'resolved', 'dismissed'])->default('pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_tickets');
    }
};