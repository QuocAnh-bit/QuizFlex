<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('rooms', 'name')) {
                $table->string('name')->nullable()->after('quiz_id');
            }

            if (!Schema::hasColumn('rooms', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('rooms', 'type')) {
                $table->string('type', 32)->default('homework')->after('description')->index();
            }
        });

        if ($this->isMysql()) {
            DB::statement("ALTER TABLE rooms MODIFY quiz_id BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE rooms MODIFY status ENUM('active', 'archived', 'waiting', 'in_progress', 'finished') NOT NULL DEFAULT 'active'");
        }

        DB::table('rooms')
            ->whereNull('owner_id')
            ->update(['owner_id' => DB::raw('host_id')]);

        if (!$this->foreignKeyExists('rooms', 'rooms_owner_id_foreign')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->foreign('owner_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('room_members')) {
            Schema::create('room_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('role', ['owner', 'member'])->default('member');
                $table->enum('status', ['active', 'removed'])->default('active');
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['room_id', 'user_id']);
                $table->index(['room_id', 'role']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('room_assignments')) {
            Schema::create('room_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
                $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('deadline_at')->nullable();
                $table->integer('duration_minutes')->nullable();
                $table->integer('max_attempts')->default(1);
                $table->enum('show_result_mode', ['immediately', 'after_deadline', 'manual'])->default('immediately');
                $table->enum('status', ['draft', 'published', 'closed'])->default('published');
                $table->timestamps();

                $table->index(['room_id', 'status']);
                $table->index(['quiz_id']);
                $table->index(['assigned_by']);
                $table->index(['deadline_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
        Schema::dropIfExists('room_members');

        if ($this->foreignKeyExists('rooms', 'rooms_owner_id_foreign')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropForeign('rooms_owner_id_foreign');
            });
        }

        Schema::table('rooms', function (Blueprint $table) {
            foreach (['type', 'description', 'name', 'owner_id'] as $column) {
                if (Schema::hasColumn('rooms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if ($this->isMysql()) {
            DB::statement("ALTER TABLE rooms MODIFY status ENUM('waiting', 'in_progress', 'finished') NOT NULL DEFAULT 'waiting'");
            DB::statement("ALTER TABLE rooms MODIFY quiz_id BIGINT UNSIGNED NOT NULL");
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
