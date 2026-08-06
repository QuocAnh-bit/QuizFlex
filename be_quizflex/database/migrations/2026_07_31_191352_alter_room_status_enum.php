<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')
            ->whereNotIn('status', ['waiting', 'in_progress', 'finished', 'banned'])
            ->update(['status' => 'waiting']);

        DB::statement("
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'waiting',
                'in_progress',
                'finished',
                'banned'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'waiting',
                'in_progress',
                'finished'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }
};
