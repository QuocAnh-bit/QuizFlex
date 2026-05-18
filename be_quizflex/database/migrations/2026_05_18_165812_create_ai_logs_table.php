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
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('quiz_id')->nullable()->index()->comment('NULL nếu sinh thất bại');
            $table->enum('action_type', ['ocr_upload', 'ai_generate']);
            $table->string('file_path')->nullable()->comment('Path file PDF/ảnh đã upload');
            $table->integer('tokens_used')->default(0);
            $table->integer('questions_generated')->default(0);
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
