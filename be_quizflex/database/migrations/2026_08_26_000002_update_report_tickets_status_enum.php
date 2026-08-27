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
        if (Schema::hasTable('report_tickets')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE report_tickets
                    MODIFY COLUMN status ENUM('pending', 'author_updated', 'admin_review_required', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending'
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_tickets')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE report_tickets
                    MODIFY COLUMN status ENUM('pending', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending'
                ");
            }
        }
    }
};
