<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Chuyển dữ liệu cũ trước khi đổi ENUM
        DB::table('rooms')
            ->where('status', 'active')
            ->update([
                'status' => 'waiting', // hoặc 'in_progress' nếu phù hợp với logic dự án
            ]);

        DB::statement("
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'active',
                'archived',
                'waiting',
                'in_progress',
                'finished',
                'closed',
                'removed',
                'banned'
            ) NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'active',
                'archived',
                'waiting',
                'in_progress',
                'finished',
                'closed',
                'removed'
            ) NOT NULL DEFAULT 'active'
        ");
    }
};