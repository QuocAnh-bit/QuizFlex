<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_chunks', function (Blueprint $table) {
            $table->unique(
                ['unit_id', 'chunk_index'],
                'curriculum_chunks_unit_index_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_chunks', function (Blueprint $table) {
            $table->dropUnique(
                'curriculum_chunks_unit_index_unique'
            );
        });
    }
};
