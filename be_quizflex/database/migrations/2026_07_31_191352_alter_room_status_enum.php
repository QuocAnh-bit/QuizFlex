<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
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
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE rooms
            MODIFY COLUMN status ENUM(
                'active',
                'archived',
                'waiting',
                'in_progress',
                'finished',
                'closed',
                'removed'
            ) NOT NULL DEFAULT 'active'
        ");
    }
};