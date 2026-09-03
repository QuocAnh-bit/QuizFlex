<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // Toàn bộ logic phát triển đã được chuyển giao vào DatabaseSeeder chuẩn
        $this->call(DatabaseSeeder::class);
    }
}
