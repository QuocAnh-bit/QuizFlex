<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_tickets') && !Schema::hasColumn('report_tickets', 'has_author_updated')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                $table->boolean('has_author_updated')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('report_tickets') && Schema::hasColumn('report_tickets', 'has_author_updated')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                $table->dropColumn('has_author_updated');
            });
        }
    }
};
