<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'room_id')) {
                $table->foreignId('room_id')
                    ->nullable()
                    ->after('quiz_id')
                    ->constrained('rooms')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('quiz_attempts', 'assignment_id')) {
                $table->foreignId('assignment_id')
                    ->nullable()
                    ->after('room_id')
                    ->constrained('room_assignments')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('quiz_attempts', 'live_session_id')) {
                $table->foreignId('live_session_id')
                    ->nullable()
                    ->after('assignment_id')
                    ->constrained('live_sessions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('quiz_attempts', 'attempt_type')) {
                $table->enum('attempt_type', ['practice', 'homework', 'live'])
                    ->default('practice')
                    ->after('live_session_id');
            }

            if (!Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('attempt_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $columns = [
                'room_id',
                'assignment_id',
                'live_session_id',
                'attempt_type',
                'submitted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('quiz_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
