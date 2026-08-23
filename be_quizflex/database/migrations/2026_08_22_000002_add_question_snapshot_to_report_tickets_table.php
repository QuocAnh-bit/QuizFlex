<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_tickets') && !Schema::hasColumn('report_tickets', 'question_snapshot')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                $table->json('question_snapshot')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('report_tickets') && Schema::hasColumn('report_tickets', 'question_snapshot')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                $table->dropColumn('question_snapshot');
            });
        }
    }
};
