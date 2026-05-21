<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_assignment_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('room_assignment_answers', 'selected_answer_ids')) {
                $table->json('selected_answer_ids')
                    ->nullable()
                    ->after('answer_id')
                    ->comment('Array of selected answer IDs for multiple choice questions');
            }
        });

        Schema::table('live_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('live_answers', 'selected_answer_ids')) {
                $table->json('selected_answer_ids')
                    ->nullable()
                    ->after('answer_id')
                    ->comment('Array of selected answer IDs for multiple choice questions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_answers', function (Blueprint $table) {
            if (Schema::hasColumn('live_answers', 'selected_answer_ids')) {
                $table->dropColumn('selected_answer_ids');
            }
        });

        Schema::table('room_assignment_answers', function (Blueprint $table) {
            if (Schema::hasColumn('room_assignment_answers', 'selected_answer_ids')) {
                $table->dropColumn('selected_answer_ids');
            }
        });
    }
};
