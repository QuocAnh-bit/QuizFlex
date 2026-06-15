<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('order_code', 100)->unique()->comment('Mã đơn gửi tới cổng thanh toán');
            $table->decimal('amount', 10);
            $table->enum('provider', ['momo', 'vnpay']);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending')->index();
            $table->string('transaction_id')->nullable()->comment('ID giao dịch từ cổng thanh toán');
            $table->json('provider_response')->nullable()->comment('Raw response từ MoMo / VNPay');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
