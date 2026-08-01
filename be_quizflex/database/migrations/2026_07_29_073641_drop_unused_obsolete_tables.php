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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('room_players');
        Schema::dropIfExists('live_answers');
        Schema::dropIfExists('live_participants');
        Schema::dropIfExists('live_sessions');
        Schema::dropIfExists('room_assignment_answers');
        Schema::dropIfExists('room_assignment_submissions');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không khôi phục các bảng thừa đã xóa
    }
};
