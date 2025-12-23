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
        Schema::create('device_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_device_id')->constrained('user_devices')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable()->comment('GPS accuracy in meters');
            $table->decimal('altitude', 10, 2)->nullable()->comment('Altitude in meters');
            $table->decimal('speed', 10, 2)->nullable()->comment('Speed in m/s');
            $table->decimal('heading', 6, 2)->nullable()->comment('Direction in degrees (0-360)');
            $table->string('api_endpoint')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_device_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_locations');
    }
};
