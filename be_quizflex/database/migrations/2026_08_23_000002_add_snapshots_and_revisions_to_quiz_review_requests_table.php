<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quiz_review_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_review_requests', 'revision_number')) {
                $table->unsignedInteger('revision_number')->default(1)->after('user_id');
            }

            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_title')) {
                $table->string('snapshot_title')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_description')) {
                $table->text('snapshot_description')->nullable()->after('snapshot_title');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_education_level_id')) {
                $table->unsignedBigInteger('snapshot_education_level_id')->nullable()->after('snapshot_description');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_grade_id')) {
                $table->unsignedBigInteger('snapshot_grade_id')->nullable()->after('snapshot_education_level_id');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_subject_id')) {
                $table->unsignedBigInteger('snapshot_subject_id')->nullable()->after('snapshot_grade_id');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_topic_name')) {
                $table->string('snapshot_topic_name')->nullable()->after('snapshot_subject_id');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_time_limit_minutes')) {
                $table->unsignedInteger('snapshot_time_limit_minutes')->nullable()->after('snapshot_topic_name');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_shuffle_questions')) {
                $table->boolean('snapshot_shuffle_questions')->default(true)->after('snapshot_time_limit_minutes');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_cover')) {
                $table->text('snapshot_cover')->nullable()->after('snapshot_shuffle_questions');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_questions')) {
                $table->json('snapshot_questions')->nullable()->after('snapshot_cover');
            }
            if (!Schema::hasColumn('quiz_review_requests', 'snapshot_metadata')) {
                $table->json('snapshot_metadata')->nullable()->after('snapshot_questions');
            }

            $table->index(['quiz_id', 'revision_number'], 'idx_quiz_review_quiz_revision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_review_requests', function (Blueprint $table) {
            $table->dropIndex('idx_quiz_review_quiz_revision');

            $columnsToDrop = [
                'revision_number',
                'snapshot_title',
                'snapshot_description',
                'snapshot_education_level_id',
                'snapshot_grade_id',
                'snapshot_subject_id',
                'snapshot_topic_name',
                'snapshot_time_limit_minutes',
                'snapshot_shuffle_questions',
                'snapshot_cover',
                'snapshot_questions',
                'snapshot_metadata',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('quiz_review_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
