<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'shuffle_questions',
                'shuffle_answers',
            ]);
        });
    }
};
