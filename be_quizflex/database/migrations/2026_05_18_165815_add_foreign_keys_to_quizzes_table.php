<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_quizzes_table.php
            $table->foreign(['user_id'], 'fk_quizzes_user_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
========
            $table->boolean('is_ai_generated')->default(false)->after('user_id');
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_31_060842_add_columns_to_quizzes_table.php
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_quizzes_table.php
            $table->dropForeign('fk_quizzes_user_id');
========
            $table->dropColumn(['is_ai_generated']);
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_31_060842_add_columns_to_quizzes_table.php
        });
    }
};
