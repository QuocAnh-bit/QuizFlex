<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_room_players')) {
            return;
        }

        Schema::create('live_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_room_id')->constrained('live_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->enum('status', ['joined', 'left'])->default('joined')->index();
            $table->timestamps();

            $table->unique(['live_room_id', 'user_id']);
            $table->index(['live_room_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_room_players');
    }
};
