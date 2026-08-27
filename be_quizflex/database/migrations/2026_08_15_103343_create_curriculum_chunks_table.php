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
        Schema::create('curriculum_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('curriculum_units')
                ->cascadeOnDelete();

            /*
         * Nếu một unit bị chia thành 3 chunk:
         *
         * 0
         * 1
         * 2
         */
            $table->unsignedInteger('chunk_index')
                ->default(0);

            // Nội dung thật của chunk
            $table->longText('content');

            /*
         * Text được chuẩn hóa để gửi
         * sang OpenRouter embedding
         */
            $table->longText('embedding_text');

            // Chỉ để debug/thống kê
            $table->unsignedInteger('estimated_tokens')
                ->nullable();

            /*
         * Hash nội dung.
         * Giúp biết chunk thay đổi hay chưa.
         */
            $table->string('content_hash', 64)
                ->index();

            /*
         * Ví dụ:
         * qwen/qwen3-embedding-8b
         */
            $table->string('embedding_model')
                ->nullable();

            $table->enum('embedding_status', [
                'pending',
                'processing',
                'embedded',
                'failed',
            ])->default('pending');

            /*
         * ID tương ứng bên Qdrant
         */
            $table->string('qdrant_point_id')
                ->nullable();

            $table->text('embedding_error')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'unit_id',
                'chunk_index',
            ]);

            $table->index('embedding_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_chunks');
    }
};
