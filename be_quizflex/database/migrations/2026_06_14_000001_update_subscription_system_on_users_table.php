<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Chuyển cột role từ enum sang string để dễ mở rộng
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('FREE')->change();
        });

        // 2. Thêm cột trial_used_at để theo dõi xem tài khoản đã dùng thử chưa
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'trial_used_at')) {
                $table->timestamp('trial_used_at')->nullable()->after('vip_expires_at');
            }
        });

        // 3. Migrate các role cũ sang tên mới
        DB::table('users')->where('role', 'USER')->update(['role' => 'FREE']);
        DB::table('users')->where('role', 'VIP')->update(['role' => 'PRO']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'trial_used_at')) {
                $table->dropColumn('trial_used_at');
            }
        });

        // Quay lại kiểu string mặc định cũ
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('USER')->change();
        });
    }
};
