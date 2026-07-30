<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role chỉ còn: admin, user
            $table->string('role', 20)->default('user')->change();

            // Plan: free, plus, pro, ultra
            if (!Schema::hasColumn('users', 'plan')) {
                $table->string('plan', 20)->default('free')->after('role');
            }
            if (!Schema::hasColumn('users', 'plan_started_at')) {
                $table->timestamp('plan_started_at')->nullable()->after('plan');
            }
            if (!Schema::hasColumn('users', 'plan_expires_at')) {
                $table->timestamp('plan_expires_at')->nullable()->after('plan_started_at');
            }
            if (!Schema::hasColumn('users', 'is_main_admin')) {
                $table->boolean('is_main_admin')->default(false)->after('plan_expires_at');
            }
        });

        // Migrate dữ liệu cũ: role -> plan + role
        // PLUS/PRO/ULTRA/FREE -> plan, role = user
        // ADMIN -> role = admin
        DB::statement("UPDATE users SET
            plan = CASE
                WHEN UPPER(role) IN ('PLUS') THEN 'plus'
                WHEN UPPER(role) IN ('PRO') THEN 'pro'
                WHEN UPPER(role) IN ('ULTRA') THEN 'ultra'
                ELSE 'free'
            END,
            plan_expires_at = CASE
                WHEN UPPER(role) IN ('PLUS','PRO','ULTRA') THEN vip_expires_at
                ELSE NULL
            END,
            plan_started_at = CASE
                WHEN UPPER(role) IN ('PLUS','PRO','ULTRA') AND vip_expires_at IS NOT NULL THEN created_at
                ELSE NULL
            END,
            role = CASE
                WHEN UPPER(role) = 'ADMIN' THEN 'admin'
                ELSE 'user'
            END
        ");

        // Đánh dấu admin đầu tiên là main admin
        DB::statement("UPDATE users SET is_main_admin = 1 WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_main_admin')) {
                $table->dropColumn('is_main_admin');
            }
            if (Schema::hasColumn('users', 'plan_expires_at')) {
                $table->dropColumn('plan_expires_at');
            }
            if (Schema::hasColumn('users', 'plan_started_at')) {
                $table->dropColumn('plan_started_at');
            }
            if (Schema::hasColumn('users', 'plan')) {
                $table->dropColumn('plan');
            }
        });
    }
};
