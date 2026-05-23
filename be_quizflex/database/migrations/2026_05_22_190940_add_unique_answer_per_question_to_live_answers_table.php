<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_answers', function (Blueprint $table) {
            $table->unique(
                ['session_id', 'user_id', 'question_id'],
                'live_answers_unique_user_question'
            );
        });
    }

    public function down(): void
    {
        Schema::table('live_answers', function (Blueprint $table) {
            $table->dropUnique('live_answers_unique_user_question');
        });
    }
};