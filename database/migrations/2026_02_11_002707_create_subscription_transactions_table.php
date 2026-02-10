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
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_transaction_id')->nullable()->constrained('subscription_transactions')->nullOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('success')->default(true);
            $table->string('type'); // intent, capture
            $table->string('driver'); // senangpay
            $table->decimal('amount', 10, 2);
            $table->string('reference');
            $table->string('status');
            $table->string('card_type')->nullable();
            $table->string('last_four')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
