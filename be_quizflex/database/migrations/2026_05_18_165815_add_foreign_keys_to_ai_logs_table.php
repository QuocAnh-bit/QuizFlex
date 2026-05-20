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
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->foreign(['quiz_id'], 'fk_ai_logs_quiz_id')->references(['id'])->on('quizzes')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['user_id'], 'fk_ai_logs_user_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->dropForeign('fk_ai_logs_quiz_id');
            $table->dropForeign('fk_ai_logs_user_id');
        });
    }
};
