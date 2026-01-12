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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable(); // URL or path to icon image
            $table->string('title'); // e.g., "Basic Plan", "Premium Plan"
            $table->string('pricing_title'); // e.g., "RM 59.90 / month"
            $table->text('description')->nullable(); // Plan description
            $table->integer('max_devices')->default(1); // Maximum number of devices allowed
            $table->decimal('price', 10, 2); // Monthly price
            $table->boolean('is_active')->default(true); // Enable/disable plan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
