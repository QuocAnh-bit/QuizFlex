<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'room_id')) {
                $table->unsignedBigInteger('room_id')->nullable()->after('quiz_id')->index();
            }

            if (!Schema::hasColumn('quiz_attempts', 'assignment_id')) {
                $table->unsignedBigInteger('assignment_id')->nullable()->after('room_id')->index();
            }

            if (!Schema::hasColumn('quiz_attempts', 'mode')) {
                $table->string('mode', 32)->default('practice')->after('assignment_id')->index();
            }

            if (!Schema::hasColumn('quiz_attempts', 'attempt_number')) {
                $table->integer('attempt_number')->nullable()->after('mode');
            }

            if (!Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('finished_at');
            }
        });

        if ($this->isMysql()) {
            DB::statement("ALTER TABLE quiz_attempts MODIFY status ENUM('in_progress', 'completed', 'expired', 'abandoned') NOT NULL DEFAULT 'in_progress'");
        }

        if (!$this->foreignKeyExists('quiz_attempts', 'quiz_attempts_room_id_foreign')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->foreign('room_id')
                    ->references('id')
                    ->on('rooms')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (!$this->foreignKeyExists('quiz_attempts', 'quiz_attempts_assignment_id_foreign')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->foreign('assignment_id')
                    ->references('id')
                    ->on('room_assignments')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'quiz_attempts_assignment_id_foreign',
            'quiz_attempts_room_id_foreign',
        ] as $foreignKey) {
            if ($this->foreignKeyExists('quiz_attempts', $foreignKey)) {
                Schema::table('quiz_attempts', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            foreach (['submitted_at', 'attempt_number', 'mode', 'assignment_id', 'room_id'] as $column) {
                if (Schema::hasColumn('quiz_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if ($this->isMysql()) {
            DB::statement("ALTER TABLE quiz_attempts MODIFY status ENUM('in_progress', 'completed', 'abandoned') NOT NULL DEFAULT 'in_progress'");
        }
    }

    private function isMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        if (!$this->isMysql()) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }
};
