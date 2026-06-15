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
                'role' => 'admin',
                'ai_quota_remaining' => 999,
            ],
            [
                'id' => 2,
                'name' => 'VIP User',
                'email' => 'vip@test.com',
                'password' => Hash::make('password'),
                'role' => 'vip',
                'vip_expires_at' => now()->addYear(),
                'ai_quota_remaining' => 50,
            ],
            [
                'id' => 3,
                'name' => 'Học sinh A',
                'email' => 'student_a@test.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'ai_quota_remaining' => 5,
            ],
            [
                'id' => 4,
                'name' => 'Học sinh B',
                'email' => 'student_b@test.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'ai_quota_remaining' => 5,
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
