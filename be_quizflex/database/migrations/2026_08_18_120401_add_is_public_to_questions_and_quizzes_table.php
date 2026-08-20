<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questions') && !Schema::hasColumn('questions', 'is_public')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->boolean('is_public')->default(true)->after('id');
            });
        }

        if (Schema::hasTable('quizzes') && !Schema::hasColumn('quizzes', 'is_public')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->boolean('is_public')->default(true)->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'is_public')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }

        if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'is_public')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }
};