<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasTable('room_members')) {
            Schema::create('room_members', function (Blueprint $table) {
                $table->id();

                $table->foreignId('room_id')
                    ->constrained('rooms')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->enum('role', ['owner', 'teacher', 'student'])->default('student');
                $table->enum('status', ['active', 'removed'])->default('active');

                $table->timestamp('joined_at')->nullable();

                $table->timestamps();

                $table->unique(['room_id', 'user_id']);
                $table->index(['room_id', 'role']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_members');
    }
};
