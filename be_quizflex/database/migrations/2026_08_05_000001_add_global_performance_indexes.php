<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $conn = Schema::getConnection();
        $database = $conn->getDatabaseName();
        $result = DB::select("
            SELECT COUNT(1) as count 
            FROM information_schema.statistics 
            WHERE table_schema = ? AND table_name = ? AND index_name = ?
        ", [$database, $table, $indexName]);

        return ($result[0]->count ?? 0) > 0;
    }

    public function up(): void
    {
        // 1. Index cho bảng room_members
        Schema::table('room_members', function (Blueprint $table) {
            if (!$this->hasIndex('room_members', 'idx_room_members_room_status')) {
                $table->index(['room_id', 'status'], 'idx_room_members_room_status');
            }
            if (!$this->hasIndex('room_members', 'idx_room_members_room_user_status')) {
                $table->index(['room_id', 'user_id', 'status'], 'idx_room_members_room_user_status');
            }
        });

        // 2. Index cho bảng rooms
        Schema::table('rooms', function (Blueprint $table) {
            if (!$this->hasIndex('rooms', 'idx_rooms_type_status')) {
                $table->index(['type', 'status'], 'idx_rooms_type_status');
            }
            if (!$this->hasIndex('rooms', 'idx_rooms_host_type_status')) {
                $table->index(['host_id', 'type', 'status'], 'idx_rooms_host_type_status');
            }
        });

        // 3. Index cho bảng quizzes
        Schema::table('quizzes', function (Blueprint $table) {
            if (!$this->hasIndex('quizzes', 'idx_quizzes_user_deleted')) {
                $table->index(['user_id', 'deleted_at'], 'idx_quizzes_user_deleted');
            }
            if (!$this->hasIndex('quizzes', 'idx_quizzes_public_deleted')) {
                $table->index(['is_public', 'deleted_at'], 'idx_quizzes_public_deleted');
            }
            if (!$this->hasIndex('quizzes', 'idx_quizzes_status_deleted')) {
                $table->index(['status', 'deleted_at'], 'idx_quizzes_status_deleted');
            }
        });

        // 4. Index cho bảng quiz_attempts
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!$this->hasIndex('quiz_attempts', 'idx_quiz_attempts_user_status')) {
                $table->index(['user_id', 'status'], 'idx_quiz_attempts_user_status');
            }
            if (!$this->hasIndex('quiz_attempts', 'idx_quiz_attempts_room_assignment')) {
                $table->index(['room_id', 'assignment_id', 'status'], 'idx_quiz_attempts_room_assignment');
            }
            if (!$this->hasIndex('quiz_attempts', 'idx_quiz_attempts_quiz_user')) {
                $table->index(['quiz_id', 'user_id'], 'idx_quiz_attempts_quiz_user');
            }
        });

        // 5. Index cho bảng users
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'idx_users_role_locked')) {
                $table->index(['role', 'is_locked'], 'idx_users_role_locked');
            }
        });

        // 6. Index cho bảng payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->hasIndex('payments', 'idx_payments_user_status')) {
                    $table->index(['user_id', 'status'], 'idx_payments_user_status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('room_members', function (Blueprint $table) {
            if ($this->hasIndex('room_members', 'idx_room_members_room_status')) {
                $table->dropIndex('idx_room_members_room_status');
            }
            if ($this->hasIndex('room_members', 'idx_room_members_room_user_status')) {
                $table->dropIndex('idx_room_members_room_user_status');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if ($this->hasIndex('rooms', 'idx_rooms_type_status')) {
                $table->dropIndex('idx_rooms_type_status');
            }
            if ($this->hasIndex('rooms', 'idx_rooms_host_type_status')) {
                $table->dropIndex('idx_rooms_host_type_status');
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if ($this->hasIndex('quizzes', 'idx_quizzes_user_deleted')) {
                $table->dropIndex('idx_quizzes_user_deleted');
            }
            if ($this->hasIndex('quizzes', 'idx_quizzes_public_deleted')) {
                $table->dropIndex('idx_quizzes_public_deleted');
            }
            if ($this->hasIndex('quizzes', 'idx_quizzes_status_deleted')) {
                $table->dropIndex('idx_quizzes_status_deleted');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            if ($this->hasIndex('quiz_attempts', 'idx_quiz_attempts_user_status')) {
                $table->dropIndex('idx_quiz_attempts_user_status');
            }
            if ($this->hasIndex('quiz_attempts', 'idx_quiz_attempts_room_assignment')) {
                $table->dropIndex('idx_quiz_attempts_room_assignment');
            }
            if ($this->hasIndex('quiz_attempts', 'idx_quiz_attempts_quiz_user')) {
                $table->dropIndex('idx_quiz_attempts_quiz_user');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'idx_users_role_locked')) {
                $table->dropIndex('idx_users_role_locked');
            }
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if ($this->hasIndex('payments', 'idx_payments_user_status')) {
                    $table->dropIndex('idx_payments_user_status');
                }
            });
        }
    }
};
