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
        if (Schema::hasTable('question_review_requests')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE question_review_requests
                    MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'superseded') NOT NULL DEFAULT 'pending'
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('question_review_requests')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE question_review_requests
                    MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'
                ");
            }
        }
    }
};
