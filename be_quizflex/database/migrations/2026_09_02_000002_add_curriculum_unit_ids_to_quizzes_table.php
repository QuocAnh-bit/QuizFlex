<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'curriculum_unit_ids')) {
                $table->json('curriculum_unit_ids')->nullable()->after('topic_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'curriculum_unit_ids')) {
                $table->dropColumn('curriculum_unit_ids');
            }
        });
    }
};
