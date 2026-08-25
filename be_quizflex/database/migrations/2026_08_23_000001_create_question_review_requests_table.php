<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('question_review_requests')) {
            Schema::create('question_review_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedInteger('revision_number')->default(1);
                $table->enum('status', ['pending', 'approved', 'rejected', 'superseded'])->default('pending');
                $table->text('request_note')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();

                // Snapshot dữ liệu câu hỏi tại thời điểm bấm gửi duyệt
                $table->text('snapshot_content');
                $table->string('snapshot_type')->default('single_choice');
                $table->string('snapshot_difficulty')->default('medium');
                $table->foreignId('snapshot_education_level_id')->nullable()->constrained('education_levels')->nullOnDelete();
                $table->foreignId('snapshot_grade_id')->nullable()->constrained('grades')->nullOnDelete();
                $table->foreignId('snapshot_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('snapshot_topic_name')->nullable();
                $table->integer('snapshot_points')->default(10);
                $table->text('snapshot_image_url')->nullable();
                $table->json('snapshot_answers');
                $table->json('snapshot_metadata')->nullable();

                $table->timestamps();

                $table->index(['question_id', 'revision_number'], 'idx_qrr_question_revision');
                $table->index(['question_id', 'status'], 'idx_qrr_question_status');
                $table->index(['status', 'created_at'], 'idx_qrr_status_created');
                $table->index(['user_id', 'created_at'], 'idx_qrr_user_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_review_requests');
    }
};
