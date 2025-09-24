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
        Schema::create('device_maintenances', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(0)->comment('0: pending, 1: pending_factory, 2: in_progress, 3: completed');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('maintenance_requested_at');
            $table->timestamp('factory_maintenance_requested_at')->nullable();
            $table->boolean('is_factory_approved')->default(false);
            $table->boolean('is_user_approved')->default(false);
            $table->longText('requested_at_changes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_maintenances');
    }
};
