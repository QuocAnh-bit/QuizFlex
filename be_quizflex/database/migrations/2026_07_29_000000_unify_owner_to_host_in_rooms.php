<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop khóa ngoại cũ nếu tồn tại
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $foreignKeys = $this->getForeignKeys('rooms');
            if (in_array('rooms_owner_id_foreign', $foreignKeys)) {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->dropForeign('rooms_owner_id_foreign');
                });
            }
        }

        // 2. Nếu đã có cả host_id và owner_id:
        // Copy giá trị từ owner_id sang host_id (nếu cần) rồi xóa owner_id đi
        if (Schema::hasColumn('rooms', 'host_id') && Schema::hasColumn('rooms', 'owner_id')) {
            DB::table('rooms')
                ->whereNull('host_id')
                ->update(['host_id' => DB::raw('owner_id')]);

            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('owner_id');
            });
        } 
        // 3. Nếu chỉ có owner_id mà chưa có host_id (trường hợp đổi tên cũ):
        // Đổi tên owner_id thành host_id
        elseif (Schema::hasColumn('rooms', 'owner_id') && !Schema::hasColumn('rooms', 'host_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->renameColumn('owner_id', 'host_id');
            });
        }

        // 4. Đảm bảo khóa ngoại fk_rooms_host_id tồn tại hoặc tạo lại nếu chưa có
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $foreignKeys = $this->getForeignKeys('rooms');
            if (!in_array('fk_rooms_host_id', $foreignKeys)) {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->foreign('host_id')
                        ->references('id')
                        ->on('users')
                        ->cascadeOnUpdate()
                        ->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('rooms', 'owner_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('id')->index();
            });

            DB::table('rooms')->update(['owner_id' => DB::raw('host_id')]);

            Schema::table('rooms', function (Blueprint $table) {
                $table->foreign('owner_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }
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
