<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $questions = DB::table('questions')->orderBy('id')->get(['id']);
        $total = $questions->count();
        if ($total === 0) return;

        foreach ($questions as $index => $q) {
            // Phân bổ tỷ lệ chuẩn Bộ GD&ĐT: 30% Dễ, 50% Vừa, 20% Khó
            $mod = $index % 10;
            if ($mod < 3) {
                $difficulty = 'easy';
            } elseif ($mod < 8) {
                $difficulty = 'medium';
            } else {
                $difficulty = 'hard';
            }

            DB::table('questions')->where('id', $q->id)->update([
                'difficulty' => $difficulty,
            ]);
        }
    }

    public function down(): void
    {
    }
};
