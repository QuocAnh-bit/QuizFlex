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
        Schema::create('curriculum_documents', function (Blueprint $table) {
            $table->id();

            // Môn học
            $table->string('subject', 100);

            // Tên tài liệu
            $table->string('title');

            // File PDF nguồn
            $table->string('file_path');

            // Nguồn tài liệu
            $table->string('publisher')
                ->default('Bộ Giáo dục và Đào tạo');

            // Ví dụ: 32/2018/TT-BGDĐT
            $table->string('legal_document')
                ->nullable();

            // Ví dụ: GDPT2018
            $table->string('curriculum_version')
                ->nullable();

            // Hash file để tránh import trùng
            $table->string('checksum', 64)
                ->nullable()
                ->index();

            // Tổng số trang
            $table->unsignedInteger('page_count')
                ->nullable();

            $table->enum('status', [
                'pending',
                'parsed',
                'chunked',
                'embedded',
                'failed',
            ])->default('pending');

            $table->text('error_message')
                ->nullable();

            $table->timestamps();

            $table->index('subject');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_documents');
    }
};
