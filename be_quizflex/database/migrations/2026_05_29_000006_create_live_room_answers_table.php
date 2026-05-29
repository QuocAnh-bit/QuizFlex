<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_room_answers')) {
            return;
        }

        Schema::create('live_room_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_room_id')->constrained('live_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('answers')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->integer('score_awarded')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->unique(['live_room_id', 'user_id', 'question_id']);
            $table->index(['live_room_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_room_answers');
    }
};
