<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Bổ sung cột cho rooms — KHÔNG xoá cột cũ
|--------------------------------------------------------------------------
| Chỉ thêm: name, description, type, visibility
| Mở rộng code cũ từ 6 → 12 ký tự
|
| KHÔNG thêm: starts_at, ends_at, duration_minutes (→ live_sessions)
|             max_attempts, show_result_mode      (→ room_assignments)
|             current_question_id/index           (→ live_sessions)
|             join_code                           (dùng code cũ là đủ)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {

            // ── Thêm cột mới ──────────────────────────────────
            if (!Schema::hasColumn('rooms', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('rooms', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            // live = thi trực tiếp, homework = giao bài
            if (!Schema::hasColumn('rooms', 'type')) {
                $table->enum('type', ['live', 'homework'])->default('live')->after('description');
            }

            if (!Schema::hasColumn('rooms', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('type');
            }

            // ── Mở rộng code cũ từ 6 → 12 ký tự ────────────
            // rooms.code hiện là varchar(6) — đổi thành varchar(12)
            // để đủ chỗ cho các mã phòng mới (VD: MATH10A2025)
            if (Schema::hasColumn('rooms', 'code')) {
                $table->string('code', 12)->change();
            }

            // ── Cho phép quiz_id nullable ─────────────────────
            // Homework room không cần quiz_id cố định
            // (quiz được giao qua room_assignments)
            if (Schema::hasColumn('rooms', 'quiz_id')) {
                $table->unsignedBigInteger('quiz_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            foreach (['name', 'description', 'type', 'is_active'] as $col) {
                if (Schema::hasColumn('rooms', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (Schema::hasColumn('rooms', 'code')) {
                $table->string('code', 6)->change();
            }
        });
    }
};