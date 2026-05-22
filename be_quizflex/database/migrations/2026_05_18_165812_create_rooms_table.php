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
       Schema::create('rooms', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('host_id')->index();
    // Room không còn gắn cố định 1 quiz nữa
    // Quiz sẽ được giao qua room_assignments
    $table->unsignedBigInteger('quiz_id')->nullable()->index();
    $table->string('code', 6)->unique()->comment('Mã 6 ký tự tham gia phòng');
    $table->enum('status', ['waiting', 'in_progress', 'finished'])->default('waiting')->index();
    $table->integer('max_players')->default(20);

    // Các cột phục vụ homework room
    $table->string('name')->nullable();
    $table->text('description')->nullable();
    $table->enum('type', ['homework', 'live'])->default('homework');
    $table->boolean('is_active')->default(true);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('ended_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
