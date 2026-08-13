<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE report_tickets MODIFY status ENUM('pending','needs_fix','resolved','dismissed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE report_tickets MODIFY status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending'");
    }
};