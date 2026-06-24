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
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('live_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('live_rooms', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('live_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('live_rooms', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
