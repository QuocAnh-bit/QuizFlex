<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        if (!Schema::hasTable('room_assignments')) {
            Schema::create('room_assignments', function (Blueprint $table) {
                $table->id();

                $table->foreignId('room_id')
                    ->constrained('rooms')
                    ->cascadeOnDelete();

                $table->foreignId('quiz_id')
                    ->constrained('quizzes')
                    ->cascadeOnDelete();

                $table->foreignId('assigned_by')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('title');
                $table->text('description')->nullable();

                $table->timestamp('starts_at')->nullable();
                $table->timestamp('deadline_at')->nullable();

                $table->integer('duration_minutes')->nullable();
                $table->integer('max_attempts')->default(1);

                $table->enum('show_result_mode', [
                    'immediately',
                    'after_submit',
                    'after_deadline',
                    'manual'
                ])->default('after_submit');

                $table->enum('status', ['draft', 'published', 'closed'])->default('draft');

                $table->timestamps();

                $table->index(['room_id', 'status']);
                $table->index(['quiz_id']);
                $table->index(['assigned_by']);
                $table->index(['deadline_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};
