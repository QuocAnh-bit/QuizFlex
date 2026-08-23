<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questions') && !Schema::hasColumn('questions', 'status')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('status', 20)->default('approved')->after('is_public');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'status')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
