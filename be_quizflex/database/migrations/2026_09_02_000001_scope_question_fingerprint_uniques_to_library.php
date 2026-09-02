<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quiz questions are editable snapshots and may intentionally have the same
     * content as a Bank or Personal Library question. Fingerprint uniqueness is
     * therefore scoped to standalone library records only.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_bank_fingerprint');
        DB::statement('ALTER TABLE questions DROP COLUMN bank_fingerprint');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN bank_fingerprint VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN quiz_id IS NULL AND is_public = 1 AND deleted_at IS NULL
                    THEN fingerprint
                    ELSE NULL
                END
            ) VIRTUAL AFTER fingerprint,
            ADD UNIQUE INDEX uq_questions_bank_fingerprint (bank_fingerprint)
        SQL);

        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_user_personal_fingerprint');
        DB::statement('ALTER TABLE questions DROP COLUMN user_personal_fingerprint');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN user_personal_fingerprint VARCHAR(100)
            GENERATED ALWAYS AS (
                CASE
                    WHEN quiz_id IS NULL
                        AND origin_question_id IS NULL
                        AND is_public = 0
                        AND deleted_at IS NULL
                    THEN CONCAT(user_id, ':', fingerprint)
                    ELSE NULL
                END
            ) VIRTUAL AFTER bank_fingerprint,
            ADD UNIQUE INDEX uq_questions_user_personal_fingerprint (user_personal_fingerprint)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_user_personal_fingerprint');
        DB::statement('ALTER TABLE questions DROP COLUMN user_personal_fingerprint');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN user_personal_fingerprint VARCHAR(100)
            GENERATED ALWAYS AS (
                CASE
                    WHEN origin_question_id IS NULL AND is_public = 0 AND deleted_at IS NULL
                    THEN CONCAT(user_id, ':', fingerprint)
                    ELSE NULL
                END
            ) VIRTUAL AFTER bank_fingerprint,
            ADD UNIQUE INDEX uq_questions_user_personal_fingerprint (user_personal_fingerprint)
        SQL);

        DB::statement('ALTER TABLE questions DROP INDEX uq_questions_bank_fingerprint');
        DB::statement('ALTER TABLE questions DROP COLUMN bank_fingerprint');
        DB::statement(<<<'SQL'
            ALTER TABLE questions
            ADD COLUMN bank_fingerprint VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN is_public = 1 AND deleted_at IS NULL
                    THEN fingerprint
                    ELSE NULL
                END
            ) VIRTUAL AFTER fingerprint,
            ADD UNIQUE INDEX uq_questions_bank_fingerprint (bank_fingerprint)
        SQL);
    }
};
