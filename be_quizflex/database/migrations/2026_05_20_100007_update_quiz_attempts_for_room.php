<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // quiz_attempts is reserved for standalone practice attempts.
        // Homework and live submissions use their own dedicated tables.
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
