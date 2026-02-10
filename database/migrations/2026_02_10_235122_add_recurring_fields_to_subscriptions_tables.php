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
        // Add senangpay_recurring_id to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('senangpay_recurring_id')->nullable()->after('is_active');
        });

        // Add recurring fields to user_subscriptions table
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(true)->after('payment_status');
            $table->timestamp('next_billing_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('senangpay_recurring_id');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'next_billing_at', 'cancelled_at']);
        });
    }
};
