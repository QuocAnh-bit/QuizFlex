<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table): void {
            $table->foreignId('education_level_id')
                ->nullable()
                ->after('prompt')
                ->constrained('education_levels')
                ->nullOnDelete();

            $table->foreignId('grade_id')
                ->nullable()
                ->after('education_level_id')
                ->constrained('grades')
                ->nullOnDelete();

            $table->foreignId('subject_id')
                ->nullable()
                ->after('grade_id')
                ->constrained('subjects')
                ->nullOnDelete();

            $table->string('topic_name', 150)
                ->nullable()
                ->after('subject_id');

            $table->json('curriculum_unit_ids')
                ->nullable()
                ->after('topic_name');
        });
    }

    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('education_level_id');
            $table->dropConstrainedForeignId('grade_id');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn([
                'topic_name',
                'curriculum_unit_ids',
            ]);
        });
    }
};
