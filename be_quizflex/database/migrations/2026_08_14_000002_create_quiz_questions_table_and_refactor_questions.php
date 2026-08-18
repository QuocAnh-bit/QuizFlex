<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tháo bỏ ràng buộc khoá ngoại cũ fk_questions_quiz_id trên bảng questions nếu có
        Schema::table('questions', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_questions_quiz_id');
            } catch (\Throwable $e) {
                // Đã tháo hoặc chưa tồn tại
            }
        });

        // 2. Chuyển cột quiz_id trong questions thành NULLABLE & bổ sung cột tác giả, độ khó
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->change();

            if (!Schema::hasColumn('questions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('quiz_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('questions', 'difficulty')) {
                $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->after('type');
            }
        });

        // 3. Tạo bảng trung gian quiz_questions (N-N)
        if (!Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
                $table->integer('order')->default(0);
                $table->integer('points')->default(10);
                $table->timestamps();

                // Đảm bảo không trùng 2 câu hỏi giống nhau trong 1 Quiz
                $table->unique(['quiz_id', 'question_id']);
                $table->index(['quiz_id', 'order']);
            });
        }

        // 4. Data Migration: Copy toàn bộ liên kết câu hỏi cũ vào bảng quiz_questions
        $existingQuestions = DB::table('questions')->whereNotNull('quiz_id')->get();
        foreach ($existingQuestions as $q) {
            DB::table('quiz_questions')->updateOrInsert(
                [
                    'quiz_id' => $q->quiz_id,
                    'question_id' => $q->id,
                ],
                [
                    'order' => $q->order ?? 0,
                    'points' => $q->points ?? 10,
                    'created_at' => $q->created_at ?? now(),
                    'updated_at' => $q->updated_at ?? now(),
                ]
            );

            // Cập nhật user_id của câu hỏi theo user_id của quiz chứa nó
            if (empty($q->user_id)) {
                $quiz = DB::table('quizzes')->where('id', $q->quiz_id)->first();
                if ($quiz) {
                    DB::table('questions')->where('id', $q->id)->update(['user_id' => $quiz->user_id]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');

        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'difficulty')) {
                $table->dropColumn('difficulty');
            }
            if (Schema::hasColumn('questions', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
