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
            // Virtual column và Unique Index đảm bảo mỗi origin_question_id chỉ có tối đa 1 Bank Snapshot hoạt động
            if (!Schema::hasColumn('questions', 'bank_origin_question_id')) {
                $table->unsignedBigInteger('bank_origin_question_id')
                    ->virtualAs("CASE WHEN origin_question_id IS NOT NULL AND deleted_at IS NULL THEN origin_question_id ELSE NULL END")
                    ->nullable()
                    ->after('origin_question_id');

                $table->unique('bank_origin_question_id', 'uq_questions_bank_origin_question_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'bank_origin_question_id')) {
                $table->dropUnique('uq_questions_bank_origin_question_id');
                $table->dropColumn('bank_origin_question_id');
            }
        });
    }
};
