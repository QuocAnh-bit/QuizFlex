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
        if (Schema::hasTable('quiz_review_requests')) {
            Schema::table('quiz_review_requests', function (Blueprint $table) {
                $table->index(['quiz_id', 'id'], 'idx_qrr_quiz_id_id');
            });
        }

        if (Schema::hasTable('question_review_requests')) {
            Schema::table('question_review_requests', function (Blueprint $table) {
                $table->index(['question_id', 'id'], 'idx_qrr_question_id_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_review_requests')) {
            Schema::table('quiz_review_requests', function (Blueprint $table) {
                $table->dropIndex('idx_qrr_quiz_id_id');
            });
        }

        if (Schema::hasTable('question_review_requests')) {
            Schema::table('question_review_requests', function (Blueprint $table) {
                $table->dropIndex('idx_qrr_question_id_id');
            });
        }
    }
};
