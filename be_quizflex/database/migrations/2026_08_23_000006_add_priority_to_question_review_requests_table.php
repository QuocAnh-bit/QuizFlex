<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('question_review_requests')) {
            Schema::table('question_review_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('question_review_requests', 'review_priority')) {
                    $table->enum('review_priority', ['normal', 'high'])->default('normal')->after('status');
                }
                if (!Schema::hasColumn('question_review_requests', 'is_priority')) {
                    $table->boolean('is_priority')->default(false)->after('review_priority');
                }

                $table->index(['status', 'review_priority', 'created_at'], 'idx_qrr_status_priority_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('question_review_requests')) {
            Schema::table('question_review_requests', function (Blueprint $table) {
                $table->dropIndex('idx_qrr_status_priority_created');
                if (Schema::hasColumn('question_review_requests', 'is_priority')) {
                    $table->dropColumn('is_priority');
                }
                if (Schema::hasColumn('question_review_requests', 'review_priority')) {
                    $table->dropColumn('review_priority');
                }
            });
        }
    }
};
