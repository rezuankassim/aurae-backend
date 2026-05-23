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
        // Update the machine_serial_format to remove variation digit
        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS} {V}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS}']);

        // Strip trailing " X" (space + single digit) from existing machine serial numbers
        // Match serials that are exactly 14 chars with a space at position 13 (e.g., "A10120260001 1")
        DB::table('machines')
            ->whereRaw("LENGTH(serial_number) = 14 AND SUBSTR(serial_number, 13, 1) = ' '")
            ->update(['serial_number' => DB::raw('SUBSTR(serial_number, 1, 12)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert format back to include variation digit
        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS} {V}']);
    }
};
