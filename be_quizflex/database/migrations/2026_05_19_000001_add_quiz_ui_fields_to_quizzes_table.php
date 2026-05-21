<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Thêm các trường UI cho quizzes
|--------------------------------------------------------------------------
| Xoá room_code khỏi đây — quiz không thuộc về 1 room cố định.
| Quan hệ quiz ↔ room được quản lý qua room_assignments.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'tag')) {
                $table->string('tag', 100)->nullable()->after('category');
            }

            // ❌ room_code ĐÃ BỊ XOÁ — quiz dùng ở nhiều room khác nhau
            // Dùng room_assignments.quiz_id để track quan hệ này

            if (!Schema::hasColumn('quizzes', 'cover')) {
                $table->string('cover', 255)->nullable()->after('time_limit_seconds');
            }

            if (!Schema::hasColumn('quizzes', 'icon')) {
                $table->string('icon', 32)->nullable()->after('cover');
            }

            if (!Schema::hasColumn('quizzes', 'badge')) {
                $table->string('badge', 32)->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            foreach (['badge', 'icon', 'cover', 'tag'] as $column) {
                if (Schema::hasColumn('quizzes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};