<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'tag')) {
                $table->string('tag', 100)->nullable()->after('category');
            }

            if (!Schema::hasColumn('quizzes', 'room_code')) {
                $table->string('room_code', 32)->nullable()->after('is_public')->index();
            }

            if (!Schema::hasColumn('quizzes', 'cover')) {
                $table->text('cover')->nullable()->after('time_limit_seconds');
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
            foreach (['badge', 'icon', 'cover', 'room_code', 'tag'] as $column) {
                if (Schema::hasColumn('quizzes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
