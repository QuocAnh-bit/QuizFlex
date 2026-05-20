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
        Schema::table('room_players', function (Blueprint $table) {
            $table->foreign(['room_id'], 'fk_room_players_room_id')->references(['id'])->on('rooms')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_id'], 'fk_room_players_user_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->dropForeign('fk_room_players_room_id');
            $table->dropForeign('fk_room_players_user_id');
        });
    }
};
