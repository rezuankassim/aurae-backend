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
        Schema::table('device_locations', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('user_device_id');
            $table->foreign('device_id')->references('id')->on('devices')->nullOnDelete();
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_locations', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropIndex(['device_id']);
            $table->dropColumn('device_id');
        });
    }
};
