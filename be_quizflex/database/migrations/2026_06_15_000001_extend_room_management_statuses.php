<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE rooms MODIFY status ENUM('active', 'archived', 'waiting', 'in_progress', 'finished', 'closed', 'removed') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE live_rooms MODIFY status ENUM('waiting', 'playing', 'finished', 'cancelled', 'removed') NOT NULL DEFAULT 'waiting'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('rooms')->where('status', 'closed')->update(['status' => 'active']);
        DB::table('rooms')->where('status', 'removed')->update(['status' => 'archived']);
        DB::table('live_rooms')->where('status', 'removed')->update(['status' => 'cancelled']);

        DB::statement("ALTER TABLE rooms MODIFY status ENUM('active', 'archived', 'waiting', 'in_progress', 'finished') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE live_rooms MODIFY status ENUM('waiting', 'playing', 'finished', 'cancelled') NOT NULL DEFAULT 'waiting'");
    }
};
