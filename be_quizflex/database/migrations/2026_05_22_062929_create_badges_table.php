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
    Schema::create('badges', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('description');
        $table->string('icon'); // tên icon hoặc URL
        $table->string('condition_type'); // xp_reached, streak_days, quiz_completed
        $table->integer('condition_value'); // đạt bao nhiêu thì unlock
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
