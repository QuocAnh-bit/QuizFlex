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
        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('curriculum_documents')
                ->cascadeOnDelete();

            /*
         * curriculum_content:
         * Toán, Lý, Hóa, Văn...
         *
         * literary_work:
         * Lão Hạc, Truyện Kiều...
         *
         * curriculum_rule:
         * tác phẩm bắt buộc, quy tắc lựa chọn...
         */
            $table->enum('type', [
                'curriculum_content',
                'literary_work',
                'curriculum_rule',
            ])->default('curriculum_content');

            $table->string('subject', 100);

            /*
         * Toán lớp 8:
         * grade_min = 8
         * grade_max = 8
         *
         * Văn nhóm 8-9:
         * grade_min = 8
         * grade_max = 9
         */
            $table->unsignedTinyInteger('grade_min')
                ->nullable();

            $table->unsignedTinyInteger('grade_max')
                ->nullable();

            // Tiểu học / THCS / THPT
            $table->string('education_level', 50)
                ->nullable();

            // Mạch kiến thức
            $table->string('domain')
                ->nullable();

            // Chủ đề
            $table->string('topic')
                ->nullable();

            // Ví dụ ĐỌC / VIẾT
            $table->string('section')
                ->nullable();

            $table->string('subsection')
                ->nullable();

            /*
         * Các field đặc biệt,
         * chủ yếu dùng cho Ngữ văn
         */
            $table->string('title')
                ->nullable();

            $table->string('author')
                ->nullable();

            $table->string('genre')
                ->nullable();

            /*
         * mandatory
         * mandatory_selection
         * suggested
         */
            $table->string('selection_type', 50)
                ->nullable();

            // Nội dung học
            $table->longText('content')
                ->nullable();

            // Yêu cầu cần đạt
            $table->json('learning_outcomes')
                ->nullable();

            // Trang PDF nguồn
            $table->unsignedInteger('source_page_start')
                ->nullable();

            $table->unsignedInteger('source_page_end')
                ->nullable();

            /*
         * Ví dụ:
         * ai-parser-v1
         */
            $table->string('parser_version', 50)
                ->nullable();

            /*
         * Sau này có thể cho admin/giáo viên
         * xác nhận dữ liệu parse
         */
            $table->boolean('is_verified')
                ->default(false);

            $table->timestamps();

            $table->index('subject');
            $table->index('type');

            $table->index([
                'subject',
                'grade_min',
                'grade_max',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_units');
    }
};
