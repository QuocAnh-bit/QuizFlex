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
        Schema::table('report_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('report_tickets', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('report_tickets', 'warning_sent_at')) {
                $table->timestamp('warning_sent_at')->nullable()->after('reminder_sent_at');
            }
            if (!Schema::hasColumn('report_tickets', 'auto_privatized_at')) {
                $table->timestamp('auto_privatized_at')->nullable()->after('warning_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_tickets', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('report_tickets', 'reminder_sent_at')) {
                $columnsToDrop[] = 'reminder_sent_at';
            }
            if (Schema::hasColumn('report_tickets', 'warning_sent_at')) {
                $columnsToDrop[] = 'warning_sent_at';
            }
            if (Schema::hasColumn('report_tickets', 'auto_privatized_at')) {
                $columnsToDrop[] = 'auto_privatized_at';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
