<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_quizzes_user_id')
                ->references(['id'])
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->boolean('is_ai_generated')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign('fk_quizzes_user_id');
            $table->dropColumn('is_ai_generated');
        });
    }
};
