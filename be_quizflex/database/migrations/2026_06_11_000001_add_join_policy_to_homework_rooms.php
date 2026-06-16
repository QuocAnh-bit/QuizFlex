<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'join_policy')) {
                $table->string('join_policy', 32)->default('open')->after('max_players')->index();
            }
        });

        if (!Schema::hasTable('room_allowed_members')) {
            Schema::create('room_allowed_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->string('email');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 32)->default('active')->index();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['room_id', 'email']);
                $table->index(['room_id', 'status']);
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_allowed_members');

        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'join_policy')) {
                $table->dropColumn('join_policy');
            }
        });
    }
};
