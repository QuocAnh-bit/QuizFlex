<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Tạo các tài khoản người dùng mẫu chuẩn chuyên nghiệp
        $demoUsers = [
            [
                'email' => 'admin@quizflex.vn',
                'name' => 'QuizFlex Admin',
                'password' => bcrypt('password'),
                'role' => 'ADMIN',
                'ai_quota_remaining' => 999,
            ],
            [
                'email' => 'admin@quizflex.local',
                'name' => 'Hệ Thống Admin',
                'password' => bcrypt('password'),
                'role' => 'ADMIN',
                'ai_quota_remaining' => 999,
            ],
            [
                'email' => 'teacher.nguyen@quizflex.vn',
                'name' => 'Thầy Nguyễn Văn An (Giáo viên Chuyên Toán)',
                'password' => bcrypt('password'),
                'role' => 'PRO',
                'ai_quota_remaining' => 200,
            ],
            [
                'email' => 'teacher.le@quizflex.vn',
                'name' => 'Cô Lê Thị Mai (Giáo viên Ngoại Ngữ)',
                'password' => bcrypt('password'),
                'role' => 'ULTRA',
                'ai_quota_remaining' => 500,
            ],
            [
                'email' => 'hocsinh.pham@quizflex.vn',
                'name' => 'Phạm Minh Đức (Học sinh 12A1)',
                'password' => bcrypt('password'),
                'role' => 'PLUS',
                'ai_quota_remaining' => 50,
            ],
            [
                'email' => 'hocsinh.tran@quizflex.vn',
                'name' => 'Trần Hoàng Nam (Học sinh 12A2)',
                'password' => bcrypt('password'),
                'role' => 'FREE',
                'ai_quota_remaining' => 20,
            ],
        ];

        foreach ($demoUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // 2. Chạy các Seeder dữ liệu chuẩn
        $this->call([
            BadgeSeeder::class,
            QuizSeeder::class,
            QuizAttemptSeeder::class,
        ]);
    }
}
