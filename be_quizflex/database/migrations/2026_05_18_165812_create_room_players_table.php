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
        Schema::create('room_players', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('user_id')->index('fk_room_players_user_id');
            $table->integer('score')->default(0);
            $table->integer('rank')->nullable()->comment('Xếp hạng sau khi kết thúc');
            $table->boolean('is_ready')->default(false);
            $table->timestamp('joined_at')->useCurrent();

            $table->index(['room_id', 'score']);
            $table->unique(['room_id', 'user_id'], 'room_players_room_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_players');
    }
};
