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
        Schema::table('ai_jobs', function (Blueprint $table) {
            // Thêm cột current_step
            $table->string('current_step')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            // Xóa cột nếu cần rollback
            $table->dropColumn('current_step');
        });
    }
};