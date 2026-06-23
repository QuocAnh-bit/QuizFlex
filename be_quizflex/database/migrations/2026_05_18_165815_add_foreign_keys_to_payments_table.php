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
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_payments_table.php
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_payments_user_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
========
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign(['quiz_id'], 'fk_questions_quiz_id')->references(['id'])->on('quizzes')->onUpdate('cascade')->onDelete('cascade');
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_questions_table.php
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_payments_table.php
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('fk_payments_user_id');
========
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign('fk_questions_quiz_id');
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_questions_table.php
        });
    }
};
