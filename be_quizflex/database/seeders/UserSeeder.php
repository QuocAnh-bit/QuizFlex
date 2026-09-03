<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Khởi tạo danh sách người dùng với danh tính thực tế, phân quyền và gói thành viên
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        $users = [
            // =========================================================================
            // 1. BAN QUẢN TRỊ HỆ THỐNG (ADMIN)
            // =========================================================================
            [
                'email' => 'admin@quizflex.vn',
                'name' => 'Trần Hoàng Long',
                'password' => $defaultPassword,
                'role' => 'admin',
                'plan' => 'ultra',
                'is_main_admin' => true,
                'ai_quota_remaining' => 9999,
                'avatar' => 'https://api.dicebear.com/7.x/bottts/svg?seed=AdminLong',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'admin@quizflex.local',
                'name' => 'Nguyễn Anh Tuấn',
                'password' => $defaultPassword,
                'role' => 'admin',
                'plan' => 'ultra',
                'is_main_admin' => false,
                'ai_quota_remaining' => 9999,
                'avatar' => 'https://api.dicebear.com/7.x/bottts/svg?seed=AdminTuan',
                'email_verified_at' => now(),
            ],

            // =========================================================================
            // 2. GIÁO VIÊN & CHUYÊN GIA BỘ MÔN (VIP - PRO)
            // =========================================================================
            [
                'email' => 'thay.duchoang@quizflex.vn',
                'name' => 'Thầy Hoàng Minh Đức',
                'password' => $defaultPassword,
                'role' => 'pro',
                'plan' => 'pro',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(2),
                'plan_started_at' => now()->subMonths(3),
                'plan_expires_at' => now()->addYears(2),
                'ai_quota_remaining' => 500,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=ThayDucHoang&gender=male',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'co.quynhnga@quizflex.vn',
                'name' => 'Cô Phạm Quỳnh Nga',
                'password' => $defaultPassword,
                'role' => 'pro',
                'plan' => 'pro',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(2),
                'plan_started_at' => now()->subMonths(2),
                'plan_expires_at' => now()->addYears(2),
                'ai_quota_remaining' => 500,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=CoQuynhNga&gender=female',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'thay.quocbao@quizflex.vn',
                'name' => 'Thầy Trần Quốc Bảo',
                'password' => $defaultPassword,
                'role' => 'pro',
                'plan' => 'pro',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(2),
                'plan_started_at' => now()->subMonths(1),
                'plan_expires_at' => now()->addYears(2),
                'ai_quota_remaining' => 500,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=ThayQuocBao&gender=male',
                'email_verified_at' => now(),
            ],

            // =========================================================================
            // 3. TÁC GIẢ KHÓA HỌC & CHUYÊN VIÊN KHẢO THÍ (VIP - ULTRA)
            // =========================================================================
            [
                'email' => 'lethanhha@gmail.com',
                'name' => 'Lê Thanh Hà',
                'password' => $defaultPassword,
                'role' => 'ultra',
                'plan' => 'ultra',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(3),
                'plan_started_at' => now()->subMonths(5),
                'plan_expires_at' => now()->addYears(3),
                'ai_quota_remaining' => 1500,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=LeThanhHa&gender=female',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'nguyenduyanh@gmail.com',
                'name' => 'Nguyễn Duy Anh',
                'password' => $defaultPassword,
                'role' => 'ultra',
                'plan' => 'ultra',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(3),
                'plan_started_at' => now()->subMonths(4),
                'plan_expires_at' => now()->addYears(3),
                'ai_quota_remaining' => 1500,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=NguyenDuyAnh&gender=male',
                'email_verified_at' => now(),
            ],

            // =========================================================================
            // 4. HỌC VIÊN CHUYÊN CẦN & ÔN THI ĐẠI HỌC (VIP - PLUS)
            // =========================================================================
            [
                'email' => 'nguyenkhanhlinh@gmail.com',
                'name' => 'Nguyễn Khánh Linh',
                'password' => $defaultPassword,
                'role' => 'plus',
                'plan' => 'plus',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(1),
                'plan_started_at' => now()->subMonths(1),
                'plan_expires_at' => now()->addYears(1),
                'ai_quota_remaining' => 200,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=NguyenKhanhLinh&gender=female',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'phanminhkhoi@gmail.com',
                'name' => 'Phan Minh Khôi',
                'password' => $defaultPassword,
                'role' => 'plus',
                'plan' => 'plus',
                'is_main_admin' => false,
                'vip_expires_at' => now()->addYears(1),
                'plan_started_at' => now()->subMonths(2),
                'plan_expires_at' => now()->addYears(1),
                'ai_quota_remaining' => 200,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=PhanMinhKhoi&gender=male',
                'email_verified_at' => now(),
            ],

            // =========================================================================
            // 5. HỌC SINH PHỔ THÔNG & THÀNH VIÊN CỘNG ĐỒNG (FREE)
            // =========================================================================
            [
                'email' => 'vuminhquan@gmail.com',
                'name' => 'Vũ Minh Quân',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=VuMinhQuan&gender=male',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'dangthuylinh@gmail.com',
                'name' => 'Đặng Thùy Linh',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=DangThuyLinh&gender=female',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'hoangvietanh@gmail.com',
                'name' => 'Hoàng Việt Anh',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=HoangVietAnh&gender=male',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'dohuyenmy@gmail.com',
                'name' => 'Đỗ Huyền My',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=DoHuyenMy&gender=female',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'nguyenthephong@gmail.com',
                'name' => 'Nguyễn Thế Phong',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=NguyenThePhong&gender=male',
                'email_verified_at' => now(),
            ],
            [
                'email' => 'buitrungkien@gmail.com',
                'name' => 'Bùi Trung Kiên',
                'password' => $defaultPassword,
                'role' => 'free',
                'plan' => 'free',
                'is_main_admin' => false,
                'ai_quota_remaining' => 30,
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=BuiTrungKien&gender=male',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Đã khởi tạo thành công danh sách ' . count($users) . ' người dùng chuẩn thực tế!');
    }
}
