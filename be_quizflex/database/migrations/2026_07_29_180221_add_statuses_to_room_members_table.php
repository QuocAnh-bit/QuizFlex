<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE room_members MODIFY status ENUM('active', 'removed', 'pending', 'blocked') NOT NULL DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('room_members')->whereIn('status', ['pending', 'blocked'])->update(['status' => 'removed']);
            DB::statement("ALTER TABLE room_members MODIFY status ENUM('active', 'removed') NOT NULL DEFAULT 'active'");
        }
    }
};
