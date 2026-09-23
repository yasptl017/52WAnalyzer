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
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('gateway', 30)->default('RAZORPAY'); // RAZORPAY, CASHFREE, STRIPE, MANUAL
            $table->string('gateway_order_id', 100)->nullable()->index();
            $table->string('gateway_payment_id', 100)->nullable()->index();
            $table->string('gateway_signature', 255)->nullable();
            $table->string('plan_tier', 20); // STARTER, PRO, ELITE
            $table->string('billing_cycle', 20)->default('MONTHLY'); // MONTHLY, ANNUAL
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('payment_method', 30)->default('UPI'); // UPI, CARD, NETBANKING, WALLET
            $table->string('status', 20)->default('SUCCESS'); // SUCCESS, PENDING, FAILED, REFUNDED
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
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
