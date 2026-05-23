<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('live_participants', 'current_question_index')) {
                $table->integer('current_question_index')->default(0)->after('wrong_count');
            }

            if (!Schema::hasColumn('live_participants', 'is_finished')) {
                $table->boolean('is_finished')->default(false)->after('current_question_index');
            }

            if (!Schema::hasColumn('live_participants', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('is_finished');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_participants', function (Blueprint $table) {
            if (Schema::hasColumn('live_participants', 'finished_at')) {
                $table->dropColumn('finished_at');
            }

            if (Schema::hasColumn('live_participants', 'is_finished')) {
                $table->dropColumn('is_finished');
            }

            if (Schema::hasColumn('live_participants', 'current_question_index')) {
                $table->dropColumn('current_question_index');
            }
        });
    }
};
