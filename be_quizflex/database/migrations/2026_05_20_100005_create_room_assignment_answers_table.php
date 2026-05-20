<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasTable('room_assignment_answers')) {
            Schema::create('room_assignment_answers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('submission_id')
                    ->constrained('room_assignment_submissions')
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

                $table->unique(['submission_id', 'question_id']);
                $table->index(['question_id', 'is_correct']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignment_answers');
    }
};
