<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('report_tickets')) {
            // 1. Xóa các bản ghi cũ chỉ có quiz_id mà không có question_id để đảm bảo toàn vẹn dữ liệu
            DB::table('report_tickets')->whereNull('question_id')->delete();

            // 2. Tháo bỏ foreign key và xóa cột quiz_id khỏi bảng report_tickets
            Schema::table('report_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('report_tickets', 'quiz_id')) {
                    try {
                        $table->dropForeign(['quiz_id']);
                    } catch (\Throwable $e) {
                        // Bỏ qua nếu foreign key chưa được đặt hoặc đã bị drop
                    }
                    $table->dropColumn('quiz_id');
                }
            });

            // 3. Đảm bảo question_id là NOT NULL và có các Index phục vụ kiểm tra duplicate / truy vấn
            Schema::table('report_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('report_tickets', 'question_id')) {
                    $table->unsignedBigInteger('question_id')->nullable(false)->change();
                }

                // Thêm composite index để tối ưu kiểm tra duplicate report
                $table->index(['user_id', 'question_id', 'status'], 'idx_report_user_q_status');
                $table->index(['question_id', 'status'], 'idx_report_q_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_tickets')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                $table->dropIndex('idx_report_user_q_status');
                $table->dropIndex('idx_report_q_status');

                if (!Schema::hasColumn('report_tickets', 'quiz_id')) {
                    $table->foreignId('quiz_id')->nullable()->after('user_id')->constrained('quizzes')->onDelete('cascade');
                }
                if (Schema::hasColumn('report_tickets', 'question_id')) {
                    $table->unsignedBigInteger('question_id')->nullable()->change();
                }
            });
        }
    }
};
