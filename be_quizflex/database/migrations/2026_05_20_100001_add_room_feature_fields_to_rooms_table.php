<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('rooms', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('rooms', 'type')) {
                $table->enum('type', ['live', 'homework'])->default('live')->after('description');
            }

            if (!Schema::hasColumn('rooms', 'visibility')) {
                $table->enum('visibility', ['private', 'public', 'invite'])->default('private')->after('type');
            }

            if (!Schema::hasColumn('rooms', 'join_code')) {
                $table->string('join_code', 20)->nullable()->unique()->after('visibility');
            }

            if (!Schema::hasColumn('rooms', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('join_code');
            }

            if (!Schema::hasColumn('rooms', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }

            if (!Schema::hasColumn('rooms', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('ends_at');
            }

            if (!Schema::hasColumn('rooms', 'max_attempts')) {
                $table->integer('max_attempts')->default(1)->after('duration_minutes');
            }

            if (!Schema::hasColumn('rooms', 'show_result_mode')) {
                $table->enum('show_result_mode', [
                    'immediately',
                    'after_submit',
                    'after_deadline',
                    'manual'
                ])->default('after_submit')->after('max_attempts');
            }

            if (!Schema::hasColumn('rooms', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(false)->after('show_result_mode');
            }

            if (!Schema::hasColumn('rooms', 'shuffle_answers')) {
                $table->boolean('shuffle_answers')->default(false)->after('shuffle_questions');
            }

            if (!Schema::hasColumn('rooms', 'allow_late_join')) {
                $table->boolean('allow_late_join')->default(true)->after('shuffle_answers');
            }

            if (!Schema::hasColumn('rooms', 'current_question_id')) {
                $table->unsignedBigInteger('current_question_id')->nullable()->after('allow_late_join');
            }

            if (!Schema::hasColumn('rooms', 'current_question_index')) {
                $table->integer('current_question_index')->nullable()->after('current_question_id');
            }
        });

        /**
         * Quan trọng:
         * Trước đây rooms.quiz_id thường là bắt buộc.
         * Nhưng với Homework Room kiểu "lớp lâu dài", rooms.quiz_id phải nullable.
         *
         * Nếu lệnh change() lỗi trên MySQL, chạy:
         * composer require doctrine/dbal
         */
        if (Schema::hasColumn('rooms', 'quiz_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unsignedBigInteger('quiz_id')->nullable()->change();
            });
        }

        /**
         * Nếu bảng rooms đã có status dạng string/enum cũ thì giữ nguyên.
         * Controller sẽ dùng các trạng thái:
         * waiting, playing, active, ended, cancelled, expired.
         */
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $columns = [
                'name',
                'description',
                'type',
                'visibility',
                'join_code',
                'starts_at',
                'ends_at',
                'duration_minutes',
                'max_attempts',
                'show_result_mode',
                'shuffle_questions',
                'shuffle_answers',
                'allow_late_join',
                'current_question_id',
                'current_question_index',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('rooms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
