<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasTable('room_assignment_submissions')) {
            Schema::create('room_assignment_submissions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('assignment_id')
                    ->constrained('room_assignments')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->integer('attempt_no')->default(1);

                $table->enum('status', [
                    'in_progress',
                    'submitted',
                    'late',
                    'cancelled'
                ])->default('in_progress');

                $table->integer('score')->default(0);
                $table->integer('correct_count')->default(0);
                $table->integer('wrong_count')->default(0);
                $table->integer('total_questions')->default(0);

                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();

                $table->timestamps();

                $table->unique(['assignment_id', 'user_id', 'attempt_no'], 'assignment_user_attempt_unique');
                $table->index(['assignment_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignment_submissions');
    }
};
