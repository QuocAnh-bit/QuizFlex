<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Payment;
use App\Models\Question;
use App\Models\ReportTicket;
use App\Models\UnlockRequest;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStreak;
use App\Models\UserXp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GamificationAndSystemSeeder extends Seeder
{
    /**
     * Khởi tạo dữ liệu Gamification (XP, Streak, Badges), Giao dịch thanh toán (Payments),
     * Yêu cầu mở khóa (Unlock Requests) và Báo cáo vi phạm câu hỏi (Report Tickets).
     */
    public function run(): void
    {
        $users = User::all();
        $admin = User::where('role', 'admin')->first() ?? $users->first();
        $badges = Badge::all();

        if ($users->isEmpty()) {
            $this->command->warn('Chưa có người dùng nào để gán dữ liệu Gamification.');
            return;
        }

        // =========================================================================
        // 1. GÁN XP, LEVEL VÀ STREAK CHO TỪNG NGƯỜI DÙNG
        // =========================================================================
        foreach ($users as $index => $user) {
            $baseXp = 150 + ($index * 120);
            $level = (int) floor($baseXp / 200) + 1;

            // 1.1 Cập nhật UserXp
            UserXp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'xp' => $baseXp,
                    'level' => $level,
                ]
            );

            // 1.2 Cập nhật UserStreak
            $streakDays = rand(3, 28);
            UserStreak::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'current_streak' => $streakDays,
                    'longest_streak' => max($streakDays, rand(10, 45)),
                    'last_activity_date' => now()->toDateString(),
                ]
            );

            // 1.3 Cấp phát huy hiệu (UserBadge)
            if ($badges->isNotEmpty()) {
                // Mọi người đều có huy hiệu Người mới
                $newbieBadge = $badges->firstWhere('name', 'Người mới');
                if ($newbieBadge) {
                    UserBadge::firstOrCreate(
                        ['user_id' => $user->id, 'badge_id' => $newbieBadge->id],
                        ['earned_at' => now()->subDays(rand(10, 30))]
                    );
                }

                // Người có streak >= 7 nhận huy hiệu On Fire
                if ($streakDays >= 7) {
                    $fireBadge = $badges->firstWhere('name', 'On Fire');
                    if ($fireBadge) {
                        UserBadge::firstOrCreate(
                            ['user_id' => $user->id, 'badge_id' => $fireBadge->id],
                            ['earned_at' => now()->subDays(rand(1, 5))]
                        );
                    }
                }

                // Người có XP >= 100 nhận huy hiệu Tích lũy
                if ($baseXp >= 100) {
                    $accumBadge = $badges->firstWhere('name', 'Tích lũy');
                    if ($accumBadge) {
                        UserBadge::firstOrCreate(
                            ['user_id' => $user->id, 'badge_id' => $accumBadge->id],
                            ['earned_at' => now()->subDays(rand(5, 15))]
                        );
                    }
                }

                // Giáo viên / VIP nhận huy hiệu Thiên tài & Ngôi sao
                if (in_array($user->role, ['pro', 'ultra', 'admin'], true)) {
                    $geniusBadge = $badges->firstWhere('name', 'Thiên tài');
                    if ($geniusBadge) {
                        UserBadge::firstOrCreate(
                            ['user_id' => $user->id, 'badge_id' => $geniusBadge->id],
                            ['earned_at' => now()->subDays(rand(5, 20))]
                        );
                    }
                }
            }
        }

        // =========================================================================
        // 2. KHỞI TẠO LỊCH SỬ GIAO DỊCH NÂNG CẤP VIP (PAYMENTS)
        // =========================================================================
        $vipUsers = User::whereIn('role', ['pro', 'ultra', 'plus'])->get();
        foreach ($vipUsers as $vip) {
            $amount = match ($vip->role) {
                'ultra' => 499000,
                'pro' => 299000,
                'plus' => 99000,
                default => 199000,
            };

            Payment::updateOrCreate(
                [
                    'user_id' => $vip->id,
                    'order_code' => 'ORD-' . strtoupper(Str::random(8)),
                ],
                [
                    'amount' => $amount,
                    'provider' => 'vnpay',
                    'status' => 'success',
                    'transaction_id' => 'VNP' . date('Ymd') . rand(100000, 999999),
                    'provider_response' => [
                        'vnp_ResponseCode' => '00',
                        'vnp_BankCode' => 'NCB',
                        'vnp_CardType' => 'ATM',
                    ],
                    'paid_at' => now()->subMonths(rand(1, 3)),
                ]
            );
        }

        // =========================================================================
        // 3. KHỞI TẠO YÊU CẦU MỞ KHÓA TÀI KHOẢN MẪU (UNLOCK REQUESTS)
        // =========================================================================
        $sampleStudent = User::where('role', 'free')->first();
        if ($sampleStudent && $admin) {
            UnlockRequest::updateOrCreate(
                [
                    'user_id' => $sampleStudent->id,
                    'status' => 'pending',
                ],
                [
                    'message' => 'Em chào ban quản trị, tài khoản của em bị tạm khóa do nhập sai mật khẩu nhiều lần khi chuyển thiết bị. Em kính mong ban quản trị hỗ trợ mở khóa tài khoản để em tiếp tục làm bài tập trên lớp ạ. Em xin cảm ơn!',
                    'admin_note' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );
        }

        // =========================================================================
        // 4. KHỞI TẠO BÁO CÁO VI PHẠM / GÓP Ý CÂU HỎI (REPORT TICKETS)
        // =========================================================================
        $sampleQuestions = Question::where('is_public', true)->take(3)->get();
        if ($sampleQuestions->isNotEmpty() && $sampleStudent && $admin) {
            $reportsData = [
                [
                    'reason' => 'Lỗi chính tả / công thức hiển thị',
                    'description' => 'Công thức toán học ở phần câu hỏi hiển thị ký hiệu chưa đồng bộ dấu ngoặc đơn, đề xuất bổ sung thêm phần giải thích chi tiết đáp án.',
                    'status' => 'resolved',
                    'resolution_note' => 'Đã kiểm tra và tinh chỉnh lại hiển thị công thức LaTeX chính xác.',
                ],
                [
                    'reason' => 'Đề xuất bổ sung cách giải',
                    'description' => 'Câu hỏi có thể mở rộng thêm một cách giải khác ngắn hơn cho các bạn học sinh làm bài nhanh.',
                    'status' => 'admin_review_required',
                    'resolution_note' => null,
                ],
            ];

            foreach ($sampleQuestions as $idx => $q) {
                $rep = $reportsData[$idx % count($reportsData)];
                ReportTicket::updateOrCreate(
                    [
                        'question_id' => $q->id,
                        'user_id' => $sampleStudent->id,
                    ],
                    [
                        'reason' => $rep['reason'],
                        'description' => $rep['description'],
                        'status' => $rep['status'],
                        'resolution_note' => $rep['resolution_note'],
                        'resolved_by' => $rep['status'] === 'resolved' ? $admin->id : null,
                        'resolved_at' => $rep['status'] === 'resolved' ? now()->subDays(1) : null,
                    ]
                );
            }
        }

        $this->command->info('Đã khởi tạo thành công dữ liệu Gamification (XP, Streak, Badges), Giao dịch VIP, Yêu cầu mở khóa và Báo cáo vi phạm!');
    }
}
