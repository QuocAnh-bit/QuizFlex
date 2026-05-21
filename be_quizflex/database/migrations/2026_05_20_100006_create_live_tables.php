<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Live Quiz tables
|--------------------------------------------------------------------------
| live_sessions   — mỗi buổi thi live = 1 session
| live_participants — ai tham gia buổi đó
| live_answers    — từng câu trả lời (tách riêng để dễ thống kê)
|
| Sửa so với bản cũ:
| + Thêm assignment_id vào live_sessions (liên kết về room_assignments)
| + Thêm code (join code) vào live_sessions
| + current_question_id/index ở live_sessions, KHÔNG ở rooms
*/

return new class extends Migration
{
    public function up(): void
    {
        // ── live_sessions ──────────────────────────────────────
        if (!Schema::hasTable('live_sessions')) {
            Schema::create('live_sessions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('room_id')
                      ->constrained('rooms')
                      ->cascadeOnDelete();

                $table->foreignId('quiz_id')
                      ->constrained('quizzes')
                      ->cascadeOnDelete();

                $table->foreignId('host_id')
                      ->constrained('users')
                      ->cascadeOnDelete();

                // ✅ Liên kết về bài giao (nếu live từ homework assignment)
                $table->foreignId('assignment_id')
                      ->nullable()
                      ->constrained('room_assignments')
                      ->nullOnDelete();

                // Mã join cho buổi thi này (khác với rooms.code)
                $table->string('code', 8)->unique()
                      ->comment('Mã 8 ký tự để join buổi thi live cụ thể');

                $table->enum('status', ['waiting', 'playing', 'ended', 'cancelled'])
                      ->default('waiting')
                      ->index();

                // ✅ Trạng thái câu hỏi đặt ở đây — không phải trong rooms
                $table->unsignedBigInteger('current_question_id')->nullable();
                $table->integer('current_question_index')->default(0);
                $table->integer('question_duration_sec')->default(20);

                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['room_id', 'status']);
            });
        }

        // ── live_participants ──────────────────────────────────
        if (!Schema::hasTable('live_participants')) {
            Schema::create('live_participants', function (Blueprint $table) {
                $table->id();

                $table->foreignId('session_id')
                      ->constrained('live_sessions')
                      ->cascadeOnDelete();

                $table->foreignId('user_id')
                      ->constrained('users')
                      ->cascadeOnDelete();

                $table->string('display_name')->nullable()
                      ->comment('Tên hiển thị trong phòng — mặc định là users.name');

                $table->enum('status', ['joined', 'playing', 'submitted', 'left', 'kicked'])
                      ->default('joined')
                      ->index();

                $table->integer('score')->default(0);
                $table->integer('correct_count')->default(0);
                $table->integer('wrong_count')->default(0);
                $table->integer('rank')->nullable();
                $table->boolean('is_ready')->default(false);

                $table->timestamp('joined_at')->useCurrent();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['session_id', 'user_id'], 'live_participants_session_user_unique');
                $table->index(['session_id', 'score']);
            });
        }

        // ── live_answers ───────────────────────────────────────
        if (!Schema::hasTable('live_answers')) {
            Schema::create('live_answers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('session_id')
                      ->constrained('live_sessions')
                      ->cascadeOnDelete();

                $table->foreignId('participant_id')
                      ->constrained('live_participants')
                      ->cascadeOnDelete();

                $table->foreignId('user_id')
                      ->constrained('users')
                      ->cascadeOnDelete();

                $table->foreignId('question_id')
                      ->constrained('questions')
                      ->cascadeOnDelete();

                $table->foreignId('answer_id')
                      ->nullable()
                      ->constrained('answers')
                      ->nullOnDelete();

                $table->boolean('is_correct')->default(false);
                $table->integer('score')->default(0);
                $table->integer('response_time_ms')->nullable()
                      ->comment('Thời gian trả lời tính bằng ms');

                $table->timestamp('answered_at')->nullable();
                $table->timestamps();

                // Mỗi người chỉ trả lời 1 câu 1 lần / session
                $table->unique(
                    ['session_id', 'participant_id', 'question_id'],
                    'live_one_answer_per_question'
                );
                $table->index(['session_id', 'question_id']);
                $table->index(['question_id', 'is_correct']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('live_answers');
        Schema::dropIfExists('live_participants');
        Schema::dropIfExists('live_sessions');
    }
};