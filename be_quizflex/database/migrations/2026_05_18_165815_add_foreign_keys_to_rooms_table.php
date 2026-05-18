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
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign(['host_id'], 'fk_rooms_host_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['quiz_id'], 'fk_rooms_quiz_id')->references(['id'])->on('quizzes')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign('fk_rooms_host_id');
            $table->dropForeign('fk_rooms_quiz_id');
        });
    }
};
