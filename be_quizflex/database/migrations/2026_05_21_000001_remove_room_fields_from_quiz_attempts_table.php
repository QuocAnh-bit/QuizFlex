<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'room_id' => 'quiz_attempts_room_id_foreign',
            'assignment_id' => 'quiz_attempts_assignment_id_foreign',
            'live_session_id' => 'quiz_attempts_live_session_id_foreign',
        ] as $column => $foreignKey) {
            if (!Schema::hasColumn('quiz_attempts', $column)) {
                continue;
            }

            if ($this->foreignKeyExists($foreignKey)) {
                Schema::table('quiz_attempts', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            foreach ([
                'room_id',
                'assignment_id',
                'live_session_id',
                'attempt_type',
                'submitted_at',
            ] as $column) {
                if (Schema::hasColumn('quiz_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank: quiz_attempts must remain practice-only.
    }

    private function foreignKeyExists(string $foreignKey): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return true;
        }

        return !empty(DB::select(
            'select constraint_name from information_schema.key_column_usage where table_schema = database() and table_name = ? and constraint_name = ?',
            ['quiz_attempts', $foreignKey]
        ));
    }
};
