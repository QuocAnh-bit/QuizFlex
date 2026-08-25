<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bổ sung các cột creation_mode, review_status, reviewed_by, reviewed_at, rejection_reason vào bảng quizzes
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'creation_mode')) {
                $table->enum('creation_mode', ['manual', 'auto'])
                    ->default('manual')
                    ->after('difficulty');
            }

            if (!Schema::hasColumn('quizzes', 'review_status')) {
                $table->enum('review_status', ['draft', 'pending_review', 'approved', 'rejected'])
                    ->default('draft')
                    ->after('status');
            }

            if (!Schema::hasColumn('quizzes', 'rejection_reason')) {
                $table->text('rejection_reason')
                    ->nullable()
                    ->after('review_status');
            }

            if (!Schema::hasColumn('quizzes', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('rejection_reason')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('quizzes', 'reviewed_at')) {
                $table->timestamp('reviewed_at')
                    ->nullable()
                    ->after('reviewed_by');
            }

            $table->index(['review_status', 'is_public'], 'idx_quizzes_review_public');
            $table->index(['creation_mode', 'review_status'], 'idx_quizzes_mode_review');
        });

        // 2. Bổ sung origin_question_id và fingerprint vào bảng questions (chuẩn bị cho snapshot & deduplication)
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'origin_question_id')) {
                $table->foreignId('origin_question_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('questions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('questions', 'fingerprint')) {
                $table->char('fingerprint', 64)
                    ->nullable()
                    ->after('origin_question_id');
            }

            $table->index(['fingerprint', 'is_public'], 'idx_questions_fingerprint_public');
        });

        // 3. Tạo bảng quiz_review_requests (Lưu yêu cầu duyệt công khai Quiz)
        if (!Schema::hasTable('quiz_review_requests')) {
            Schema::create('quiz_review_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('request_note')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['quiz_id', 'status'], 'idx_qrr_quiz_status');
                $table->index(['status', 'created_at'], 'idx_qrr_status_created');
            });
        }

        // 4. Data Migration an toàn: Cập nhật review_status cho các quiz hiện hữu trong hệ thống
        // Các quiz công khai đã published trước đó được gán review_status = 'approved'
        DB::table('quizzes')->where('is_public', true)->where('status', 'published')->update([
            'review_status' => 'approved',
            'creation_mode' => 'manual',
        ]);

        // Các quiz private hoặc draft được gán review_status = 'draft'
        DB::table('quizzes')->where(function ($q) {
            $q->where('is_public', false)->orWhere('status', '!=', 'published');
        })->update([
            'review_status' => 'draft',
            'creation_mode' => 'manual',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_review_requests');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_fingerprint_public');
            if (Schema::hasColumn('questions', 'fingerprint')) {
                $table->dropColumn('fingerprint');
            }
            if (Schema::hasColumn('questions', 'origin_question_id')) {
                $table->dropForeign(['origin_question_id']);
                $table->dropColumn('origin_question_id');
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('idx_quizzes_review_public');
            $table->dropIndex('idx_quizzes_mode_review');
            if (Schema::hasColumn('quizzes', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn('reviewed_by');
            }
            $columnsToDrop = [];
            foreach (['creation_mode', 'review_status', 'rejection_reason', 'reviewed_at'] as $col) {
                if (Schema::hasColumn('quizzes', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
