<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // 1. Drop khóa ngoại cũ nếu tồn tại
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $foreignKeys = $this->getForeignKeys('rooms');
                if (in_array('rooms_owner_id_foreign', $foreignKeys)) {
                    $table->dropForeign('rooms_owner_id_foreign');
                }
            }

            // 2. Đổi tên cột owner_id sang host_id
            if (Schema::hasColumn('rooms', 'owner_id')) {
                $table->renameColumn('owner_id', 'host_id');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            // 3. Tạo lại khóa ngoại mới trên host_id
            $table->foreign('host_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $foreignKeys = $this->getForeignKeys('rooms');
                if (in_array('rooms_host_id_foreign', $foreignKeys)) {
                    $table->dropForeign('rooms_host_id_foreign');
                }
            }

            if (Schema::hasColumn('rooms', 'host_id')) {
                $table->renameColumn('host_id', 'owner_id');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    private function getForeignKeys(string $table): array
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME')
            ->toArray();
    }
};
