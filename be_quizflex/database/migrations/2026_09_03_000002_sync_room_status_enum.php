<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'active',
                'archived',
                'waiting',
                'in_progress',
                'finished',
                'closed',
                'removed',
                'banned'
            ) NOT NULL DEFAULT 'active'
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('rooms')
            ->whereIn('status', ['active', 'archived', 'closed', 'removed'])
            ->update(['status' => 'waiting']);

        DB::statement(<<<'SQL'
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'waiting',
                'in_progress',
                'finished',
                'banned'
            ) NOT NULL DEFAULT 'waiting'
        SQL);
    }
};
