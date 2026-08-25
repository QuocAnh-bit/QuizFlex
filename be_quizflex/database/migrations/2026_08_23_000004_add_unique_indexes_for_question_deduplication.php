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
        Schema::table('questions', function (Blueprint $table) {
            // 1. Virtual column và Unique Index cho Question Bank Snapshot
            if (!Schema::hasColumn('questions', 'bank_fingerprint')) {
                $table->string('bank_fingerprint', 64)
                    ->virtualAs("CASE WHEN is_public = 1 AND deleted_at IS NULL THEN fingerprint ELSE NULL END")
                    ->nullable()
                    ->after('fingerprint');

                $table->unique('bank_fingerprint', 'uq_questions_bank_fingerprint');
            }

            // 2. Virtual column và Unique Index cho User Personal Questions
            if (!Schema::hasColumn('questions', 'user_personal_fingerprint')) {
                $table->string('user_personal_fingerprint', 100)
                    ->virtualAs("CASE WHEN origin_question_id IS NULL AND is_public = 0 AND deleted_at IS NULL THEN CONCAT(user_id, ':', fingerprint) ELSE NULL END")
                    ->nullable()
                    ->after('bank_fingerprint');

                $table->unique('user_personal_fingerprint', 'uq_questions_user_personal_fingerprint');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'user_personal_fingerprint')) {
                $table->dropUnique('uq_questions_user_personal_fingerprint');
                $table->dropColumn('user_personal_fingerprint');
            }

            if (Schema::hasColumn('questions', 'bank_fingerprint')) {
                $table->dropUnique('uq_questions_bank_fingerprint');
                $table->dropColumn('bank_fingerprint');
            }
        });
    }
};
