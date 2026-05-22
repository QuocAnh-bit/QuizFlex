<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'name'            => 'Người mới',
                'description'     => 'Hoàn thành quiz đầu tiên',
                'icon'            => '🚀',
                'condition_type'  => 'quiz_completed',
                'condition_value' => 1,
            ],
            [
                'name'            => 'On Fire',
                'description'     => 'Streak 7 ngày liên tiếp',
                'icon'            => '🔥',
                'condition_type'  => 'streak_days',
                'condition_value' => 7,
            ],
            [
                'name'            => 'Chăm chỉ',
                'description'     => 'Streak 30 ngày liên tiếp',
                'icon'            => '💪',
                'condition_type'  => 'streak_days',
                'condition_value' => 30,
            ],
            [
                'name'            => 'Tích lũy',
                'description'     => 'Đạt 100 XP',
                'icon'            => '⚡',
                'condition_type'  => 'xp_reached',
                'condition_value' => 100,
            ],
            [
                'name'            => 'Thiên tài',
                'description'     => 'Đạt 500 XP',
                'icon'            => '🧠',
                'condition_type'  => 'xp_reached',
                'condition_value' => 500,
            ],
            [
                'name'            => 'Huyền thoại',
                'description'     => 'Đạt 1000 XP',
                'icon'            => '👑',
                'condition_type'  => 'xp_reached',
                'condition_value' => 1000,
            ],
            [
                'name'            => 'Ngôi sao',
                'description'     => 'Vào top 10 bảng xếp hạng',
                'icon'            => '⭐',
                'condition_type'  => 'xp_reached',
                'condition_value' => 800,
            ],
            [
                'name'            => 'Huyền thoại',
                'description'     => 'Đạt top 1 bảng xếp hạng',
                'icon'            => '🏆',
                'condition_type'  => 'xp_reached',
                'condition_value' => 2000,
            ],
        ];

        DB::table('badges')->insert($badges);
    }
}
