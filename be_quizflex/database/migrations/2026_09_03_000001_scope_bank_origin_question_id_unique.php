<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Questions added to a quiz (quiz_id IS NOT NULL) are snapshots that cite
     * their origin question from the Question Bank or personal library.
     * Therefore, bank_origin_question_id uniqueness must only apply to standalone
     * question bank records (quiz_id IS NULL AND is_public = 1).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_bank_origin_question_id');
        DB::statement('ALTER TABLE questions DROP COLUMN bank_origin_question_id');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN bank_origin_question_id BIGINT UNSIGNED
            GENERATED ALWAYS AS (
                CASE
                    WHEN quiz_id IS NULL AND is_public = 1 AND origin_question_id IS NOT NULL AND deleted_at IS NULL
                    THEN origin_question_id
                    ELSE NULL
                END
            ) VIRTUAL AFTER origin_question_id,
            ADD UNIQUE INDEX uq_questions_bank_origin_question_id (bank_origin_question_id)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_bank_origin_question_id');
        DB::statement('ALTER TABLE questions DROP COLUMN bank_origin_question_id');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN bank_origin_question_id BIGINT UNSIGNED
            GENERATED ALWAYS AS (
                CASE
                    WHEN origin_question_id IS NOT NULL AND deleted_at IS NULL
                    THEN origin_question_id
                    ELSE NULL
                END
            ) VIRTUAL AFTER origin_question_id,
            ADD UNIQUE INDEX uq_questions_bank_origin_question_id (bank_origin_question_id)
        SQL);
    }
};
