<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'violation_count')) {
                $table->unsignedInteger('violation_count')->default(0)->after('status');
            }

            if (!Schema::hasColumn('quiz_attempts', 'violation_log')) {
                $table->json('violation_log')->nullable()->after('violation_count');
            }

            if (!Schema::hasColumn('quiz_attempts', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('violation_log');
            }

            if (!Schema::hasColumn('quiz_attempts', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $columns = [];

            foreach (['violation_count', 'violation_log', 'is_locked', 'locked_at'] as $column) {
                if (Schema::hasColumn('quiz_attempts', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
