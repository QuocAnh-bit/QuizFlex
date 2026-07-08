<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quizzes')
            ->where('is_public', true)
            ->where('status', 'draft')
            ->update([
                'status' => 'published',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};