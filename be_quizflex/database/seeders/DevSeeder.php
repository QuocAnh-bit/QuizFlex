<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'ai_quota_remaining' => 999,
            ],
            [
                'id' => 2,
                'name' => 'Pro User',
                'email' => 'pro@test.com',
                'password' => Hash::make('password'),
                'role' => 'PRO',
                'vip_expires_at' => now()->addYear(),
                'ai_quota_remaining' => 50,
            ],
            [
                'id' => 3,
                'name' => 'Học sinh A',
                'email' => 'student_a@test.com',
                'password' => Hash::make('password'),
                'role' => 'FREE',
                'ai_quota_remaining' => 5,
            ],
            [
                'id' => 4,
                'name' => 'Học sinh B',
                'email' => 'student_b@test.com',
                'password' => Hash::make('password'),
                'role' => 'FREE',
                'ai_quota_remaining' => 5,
            ],
            [
                'id' => 5,
                'name' => 'Học sinh C',
                'email' => 'student_c@test.com',
                'password' => Hash::make('password'),
                'role' => 'FREE',
                'ai_quota_remaining' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Học sinh D',
                'email' => 'student_d@test.com',
                'password' => Hash::make('password'),
                'role' => 'FREE',
                'ai_quota_remaining' => 5,
            ],
            [
                'id' => 7,
                'name' => 'Giáo viên Plus',
                'email' => 'teacher_plus@test.com',
                'password' => Hash::make('password'),
                'role' => 'PLUS',
                'vip_expires_at' => now()->addYear(),
                'ai_quota_remaining' => 30,
            ],
            [
                'id' => 8,
                'name' => 'Giáo viên Pro',
                'email' => 'teacher_pro@test.com',
                'password' => Hash::make('password'),
                'role' => 'PRO',
                'vip_expires_at' => now()->addYear(),
                'ai_quota_remaining' => 100,
            ],
            [
                'id' => 9,
                'name' => 'Giáo viên Ultra',
                'email' => 'teacher_ultra@test.com',
                'password' => Hash::make('password'),
                'role' => 'ULTRA',
                'vip_expires_at' => now()->addYear(),
                'ai_quota_remaining' => 999,
            ],
        ];

        foreach ($users as $data) {
            $user = User::find($data['id']);

            if ($user) {
                $user->forceFill($data)->save();
            } else {
                User::forceCreate($data);
            }
        }

        $this->command->info('DevSeeder done!');
    }
}
