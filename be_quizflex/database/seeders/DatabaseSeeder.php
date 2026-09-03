<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Chạy toàn bộ hệ thống Seeder mới cho QuizFlex
     */
    public function run(): void
    {
        $this->command->info('=== BẮT ĐẦU DỌN DẸP VÀ KHỞI TẠO DỮ LIỆU QUIZFLEX ===');

        // 1. Tắt ràng buộc khóa ngoại để dọn dẹp an toàn
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToTruncate = [
            'room_submission_evaluations',
            'room_member_evaluations',
            'room_allowed_members',
            'room_members',
            'room_assignments',
            'rooms',
            'live_room_answers',
            'live_room_players',
            'live_rooms',
            'quiz_attempts',
            'quiz_questions',
            'quizzes',
            'answers',
            'questions',
            'question_review_requests',
            'quiz_review_requests',
            'report_tickets',
            'unlock_requests',
            'payments',
            'user_badges',
            'user_streaks',
            'user_xp',
            'users',
        ];

        foreach ($tablesToTruncate as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('-> Đã xóa sạch dữ liệu cũ thành công!');

        // 2. Chạy lần lượt các Seeder theo thứ tự phụ thuộc
        $this->call([
            // 2.1 Cấp học, Khối lớp, 12 Bộ môn
            EducationTaxonomySeeder::class,

            // 2.2 Tài liệu và Đơn vị chương trình GDPT 2018 (RAG)
            CurriculumUnitSeeder::class,

            // 2.3 Danh mục huy hiệu thành tích
            BadgeSeeder::class,

            // 2.4 Người dùng với danh tính thực tế (Admin, PRO, ULTRA, PLUS, FREE)
            UserSeeder::class,

            // 2.5 Ngân hàng câu hỏi chuẩn quốc gia (12 bộ môn x 10 câu = 120 câu hỏi công khai)
            QuestionSeeder::class,

            // 2.6 Kho câu hỏi cá nhân riêng biệt cho từng người dùng
            PersonalQuestionSeeder::class,

            // 2.7 Các bài Quiz kết hợp (mix) câu hỏi Ngân hàng + Kho cá nhân kèm lượt làm bài
            QuizSeeder::class,

            // 2.8 Phòng bài tập cho tài khoản VIP (mỗi VIP có >= 2 phòng, đầy đủ bài giao, bài nộp, xuất được Excel)
            HomeworkRoomSeeder::class,

            // 2.9 Dữ liệu Gamification (XP, Streak, Huy hiệu), Giao dịch VIP, Mở khóa và Báo cáo vi phạm
            GamificationAndSystemSeeder::class,
        ]);

        $this->command->info('=== HOÀN TẤT KHỞI TẠO TOÀN BỘ CƠ SỞ DỮ LIỆU QUIZFLEX ===');
    }
}
