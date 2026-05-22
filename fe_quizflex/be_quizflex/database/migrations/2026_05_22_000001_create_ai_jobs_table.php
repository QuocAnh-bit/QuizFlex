<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('ai_log_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->text('prompt');
            $table->unsignedInteger('requested_count')->default(10);
            $table->string('difficulty', 32)->default('medium');
            $table->string('language', 32)->default('vi');
            $table->string('visibility', 32)->default('private');
            $table->unsignedInteger('questions_generated')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->unsignedBigInteger('quiz_id')->nullable()->index();
            $table->longText('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id', 'fk_ai_jobs_user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('ai_log_id', 'fk_ai_jobs_ai_log_id')
                ->references('id')
                ->on('ai_logs')
                ->onUpdate('cascade')
                ->nullOnDelete();

            $table->foreign('quiz_id', 'fk_ai_jobs_quiz_id')
                ->references('id')
                ->on('quizzes')
                ->onUpdate('cascade')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->dropForeign('fk_ai_jobs_user_id');
            $table->dropForeign('fk_ai_jobs_ai_log_id');
            $table->dropForeign('fk_ai_jobs_quiz_id');
        });

        Schema::dropIfExists('ai_jobs');
    }
};
