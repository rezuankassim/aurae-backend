<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_maintenances', function (Blueprint $table) {
            $table->enum('service_type', ['Yearly service', 'Monthly service', 'One-time service'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('device_maintenances', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
