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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('status');
            $table->string('payment_method')->nullable()->after('transaction_id');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending')->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'payment_method', 'payment_status', 'paid_at']);
        });
    }
};
