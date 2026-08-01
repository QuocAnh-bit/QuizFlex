<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cho MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY status ENUM('active', 'archived', 'waiting', 'in_progress', 'finished', 'closed', 'removed', 'banned') NOT NULL DEFAULT 'active'");
            DB::statement("ALTER TABLE live_rooms MODIFY status ENUM('waiting', 'playing', 'finished', 'cancelled', 'removed', 'banned') NOT NULL DEFAULT 'waiting'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY status ENUM('active', 'archived', 'waiting', 'in_progress', 'finished', 'closed', 'removed') NOT NULL DEFAULT 'active'");
            DB::statement("ALTER TABLE live_rooms MODIFY status ENUM('waiting', 'playing', 'finished', 'cancelled', 'removed') NOT NULL DEFAULT 'waiting'");
        }
    }
};
