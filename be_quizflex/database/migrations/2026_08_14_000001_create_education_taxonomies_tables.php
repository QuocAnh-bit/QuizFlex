<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng education_levels (Cấp học)
        if (!Schema::hasTable('education_levels')) {
            Schema::create('education_levels', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique(); // primary, secondary, high_school, university, other
                $table->string('name', 100);          // Tiểu học, THCS, THPT, Đại học / Khác
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Tạo bảng grades (Khối lớp)
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('education_level_id')->constrained('education_levels')->onDelete('cascade');
                $table->string('code', 50);          // grade_1, grade_2, ..., grade_12, uni, general
                $table->string('name', 100);          // Lớp 1, Lớp 2, ..., Lớp 12, Đại học, Tổng hợp
                $table->integer('level_number')->nullable(); // 1..12
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // 3. Tạo bảng subjects (Bộ môn)
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique(); // math, literature, english, physics, chemistry, biology, history, geography, civics, informatics, technology, skills, general
                $table->string('name', 100);          // Toán học, Ngữ văn, Tiếng Anh, Vật lý, Hóa học...
                $table->string('icon', 50)->nullable();
                $table->string('category_group', 50)->default('general'); // natural, social, foreign_language, technology, other
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // 4. Bảng liên kết môn học với khối lớp
        if (!Schema::hasTable('subject_grade')) {
            Schema::create('subject_grade', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->foreignId('grade_id')->constrained('grades')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['subject_id', 'grade_id']);
            });
        }

        // 5. Thêm các cột taxonomy vào bảng quizzes
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->after('category')->constrained('education_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('quizzes', 'grade_id')) {
                $table->foreignId('grade_id')->nullable()->after('education_level_id')->constrained('grades')->nullOnDelete();
            }
            if (!Schema::hasColumn('quizzes', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('quizzes', 'topic_name')) {
                $table->string('topic_name', 150)->nullable()->after('subject_id');
            }

            // Index phục vụ lọc nhanh
            $table->index(['education_level_id', 'grade_id', 'subject_id'], 'idx_quizzes_taxonomy');
        });

        // 6. Thêm các cột taxonomy vào bảng questions
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->after('type')->constrained('education_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('questions', 'grade_id')) {
                $table->foreignId('grade_id')->nullable()->after('education_level_id')->constrained('grades')->nullOnDelete();
            }
            if (!Schema::hasColumn('questions', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('questions', 'topic_name')) {
                $table->string('topic_name', 150)->nullable()->after('subject_id');
            }

            $table->index(['education_level_id', 'grade_id', 'subject_id'], 'idx_questions_taxonomy');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_taxonomy');
            $table->dropForeign(['education_level_id']);
            $table->dropForeign(['grade_id']);
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['education_level_id', 'grade_id', 'subject_id', 'topic_name']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('idx_quizzes_taxonomy');
            $table->dropForeign(['education_level_id']);
            $table->dropForeign(['grade_id']);
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['education_level_id', 'grade_id', 'subject_id', 'topic_name']);
        });

        Schema::dropIfExists('subject_grade');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('education_levels');
    }
};
