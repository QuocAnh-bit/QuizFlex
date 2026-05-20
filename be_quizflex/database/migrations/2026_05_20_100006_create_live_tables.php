<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasTable('live_sessions')) {
            Schema::create('live_sessions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('room_id')
                    ->constrained('rooms')
                    ->cascadeOnDelete();

                $table->foreignId('quiz_id')
                    ->constrained('quizzes')
                    ->cascadeOnDelete();

                $table->foreignId('host_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->enum('status', ['waiting', 'playing', 'ended', 'cancelled'])
                    ->default('waiting');

                $table->unsignedBigInteger('current_question_id')->nullable();
                $table->integer('current_question_index')->nullable();

                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();

                $table->timestamps();

                $table->index(['room_id', 'status']);
                $table->index(['quiz_id']);
            });
        }

        if (!Schema::hasTable('live_participants')) {
            Schema::create('live_participants', function (Blueprint $table) {
                $table->id();

                $table->foreignId('session_id')
                    ->constrained('live_sessions')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('display_name')->nullable();

                $table->enum('status', ['joined', 'playing', 'submitted', 'left', 'kicked'])
                    ->default('joined');

                $table->integer('score')->default(0);
                $table->integer('correct_count')->default(0);
                $table->integer('wrong_count')->default(0);

                $table->timestamp('joined_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();

                $table->timestamps();

                $table->unique(['session_id', 'user_id']);
                $table->index(['session_id', 'score']);
            });
        }

        if (!Schema::hasTable('live_answers')) {
            Schema::create('live_answers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('session_id')
                    ->constrained('live_sessions')
                    ->cascadeOnDelete();

                $table->foreignId('participant_id')
                    ->constrained('live_participants')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('question_id')
                    ->constrained('questions')
                    ->cascadeOnDelete();

                $table->foreignId('answer_id')
                    ->nullable()
                    ->constrained('answers')
                    ->nullOnDelete();

                $table->boolean('is_correct')->default(false);
                $table->integer('score')->default(0);
                $table->integer('response_time_ms')->nullable();

                $table->timestamp('answered_at')->nullable();

                $table->timestamps();

                $table->unique(['session_id', 'participant_id', 'question_id'], 'live_answer_unique');
                $table->index(['session_id', 'question_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('live_answers');
        Schema::dropIfExists('live_participants');
        Schema::dropIfExists('live_sessions');
    }
};
