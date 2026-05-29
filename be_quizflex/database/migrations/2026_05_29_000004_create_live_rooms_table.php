<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_rooms')) {
            return;
        }

        Schema::create('live_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('code', 12)->unique();
            $table->string('title');
            $table->enum('status', ['waiting', 'playing', 'finished', 'cancelled'])->default('waiting')->index();
            $table->unsignedInteger('current_question_index')->default(0);
            $table->json('question_order')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['host_id', 'status']);
            $table->index(['quiz_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_rooms');
    }
};
