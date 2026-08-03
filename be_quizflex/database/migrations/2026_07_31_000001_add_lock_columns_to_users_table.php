<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_main_admin');
            }

            if (!Schema::hasColumn('users', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            }

            if (!Schema::hasColumn('users', 'locked_reason')) {
                $table->text('locked_reason')->nullable()->after('locked_at');
            }

            if (!Schema::hasColumn('users', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_reason')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'locked_by')) {
                $table->dropForeign(['locked_by']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'locked_by')) {
                $table->dropColumn('locked_by');
            }
            if (Schema::hasColumn('users', 'locked_reason')) {
                $table->dropColumn('locked_reason');
            }
            if (Schema::hasColumn('users', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
            if (Schema::hasColumn('users', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
