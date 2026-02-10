<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the machine_serial_format to include space before variation code
        DB::table('general_settings')
            ->where('machine_serial_format', 'AUR-{NNNN}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS} {V}']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to format without space
        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS} {V}')
            ->update(['machine_serial_format' => 'AUR-{NNNN}']);
    }
};
