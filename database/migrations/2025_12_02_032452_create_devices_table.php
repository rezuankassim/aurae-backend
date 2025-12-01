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
        Schema::create('devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->tinyInteger('status')->default(1)->comment('1: active, 0: inactive');
            $table->string('uuid')->unique();
            $table->string('name');
            $table->date('started_at')->nullable();
            $table->date('should_end_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_logged_in_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
