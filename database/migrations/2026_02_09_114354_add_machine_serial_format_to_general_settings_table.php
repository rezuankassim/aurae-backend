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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('machine_serial_format')->default('{MMMM}{YYYY}{SSSS} {V}')->after('id');
            $table->string('machine_serial_prefix')->default('A101')->after('machine_serial_format');
            $table->integer('machine_serial_length')->default(13)->after('machine_serial_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['machine_serial_format', 'machine_serial_prefix', 'machine_serial_length']);
        });
    }
};
