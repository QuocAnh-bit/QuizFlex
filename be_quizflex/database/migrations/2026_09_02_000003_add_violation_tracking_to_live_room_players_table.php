<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_room_players', function (Blueprint $table) {
            if (!Schema::hasColumn('live_room_players', 'violation_count')) {
                $table->unsignedInteger('violation_count')->default(0)->after('status');
            }

            if (!Schema::hasColumn('live_room_players', 'violation_log')) {
                $table->json('violation_log')->nullable()->after('violation_count');
            }

            if (!Schema::hasColumn('live_room_players', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('violation_log');
            }

            if (!Schema::hasColumn('live_room_players', 'flagged_at')) {
                $table->timestamp('flagged_at')->nullable()->after('is_flagged');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_room_players', function (Blueprint $table) {
            $columns = [];

            foreach (['violation_count', 'violation_log', 'is_flagged', 'flagged_at'] as $column) {
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
