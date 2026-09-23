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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('free_trial')->after('password'); // guest, free_trial, starter, pro, elite, admin
            $table->string('subscription_tier', 20)->default('FREE')->after('role'); // FREE, STARTER, PRO, ELITE
            $table->string('subscription_status', 20)->default('TRIALING')->after('subscription_tier'); // ACTIVE, TRIALING, EXPIRED, CANCELLED
            $table->string('billing_cycle', 20)->default('MONTHLY')->after('subscription_status'); // MONTHLY, ANNUAL
            $table->timestamp('trial_ends_at')->nullable()->after('billing_cycle');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('razorpay_customer_id', 100)->nullable()->after('subscription_ends_at');
            $table->string('phone', 20)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'subscription_tier',
                'subscription_status',
                'billing_cycle',
                'trial_ends_at',
                'subscription_ends_at',
                'razorpay_customer_id',
                'phone'
            ]);
        });
    }
};
