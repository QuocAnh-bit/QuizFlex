<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_room_players', function (Blueprint $table) {
            if (!Schema::hasColumn('live_room_players', 'current_question_index')) {
                $table->unsignedInteger('current_question_index')->default(0)->after('correct_count');
            }

            if (!Schema::hasColumn('live_room_players', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('joined_at');
            }

            if (!Schema::hasColumn('live_room_players', 'last_answered_at')) {
                $table->timestamp('last_answered_at')->nullable()->after('finished_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_room_players', function (Blueprint $table) {
            $columns = [];

            foreach (['current_question_index', 'finished_at', 'last_answered_at'] as $column) {
                if (Schema::hasColumn('live_room_players', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
