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
            $table->foreignId('quiz_id')->nullable()->change();
            $table->foreignId('question_id')->nullable()->after('quiz_id')->constrained('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_tickets', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropColumn('question_id');
            $table->foreignId('quiz_id')->nullable(false)->change();
        });
    }
};
