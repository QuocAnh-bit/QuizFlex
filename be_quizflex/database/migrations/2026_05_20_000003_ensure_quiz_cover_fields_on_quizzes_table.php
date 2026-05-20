<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        $this->makeCoverColumnText();
    }

    public function down(): void
    {
        // Không drop cột ở đây để tránh mất dữ liệu ảnh bìa khi rollback nhầm.
    }

    private function makeCoverColumnText(): void
    {
        if (!Schema::hasColumn('quizzes', 'cover')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE quizzes MODIFY cover TEXT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE quizzes ALTER COLUMN cover TYPE TEXT');
        }
    }
};
