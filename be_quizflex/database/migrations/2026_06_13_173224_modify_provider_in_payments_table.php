<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_06_13_173224_modify_provider_in_payments_table.php
            $table->string('provider', 50)->change();
========
            $table->foreign(['user_id'], 'fk_payments_user_id')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_payments_table.php
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
<<<<<<<< HEAD:be_quizflex/database/migrations/2026_06_13_173224_modify_provider_in_payments_table.php
            $table->enum('provider', ['momo', 'vnpay'])->change();
========
            $table->dropForeign('fk_payments_user_id');
>>>>>>>> origin/huydev:be_quizflex/database/migrations/2026_05_18_165815_add_foreign_keys_to_payments_table.php
        });
    }
};
