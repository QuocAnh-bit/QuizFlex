<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questions')) {
            Schema::table('questions', function (Blueprint $table) {
                if (!Schema::hasColumn('questions', 'bank_submission_status')) {
                    $table->enum('bank_submission_status', ['none', 'pending', 'approved', 'rejected'])
                        ->default('none')
                        ->after('is_public');
                }
                if (!Schema::hasColumn('questions', 'bank_submission_note')) {
                    $table->text('bank_submission_note')
                        ->nullable()
                        ->after('bank_submission_status');
                }
                if (!Schema::hasColumn('questions', 'bank_submission_at')) {
                    $table->timestamp('bank_submission_at')
                        ->nullable()
                        ->after('bank_submission_note');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questions')) {
            Schema::table('questions', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('questions', 'bank_submission_status')) {
                    $columnsToDrop[] = 'bank_submission_status';
                }
                if (Schema::hasColumn('questions', 'bank_submission_note')) {
                    $columnsToDrop[] = 'bank_submission_note';
                }
                if (Schema::hasColumn('questions', 'bank_submission_at')) {
                    $columnsToDrop[] = 'bank_submission_at';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
