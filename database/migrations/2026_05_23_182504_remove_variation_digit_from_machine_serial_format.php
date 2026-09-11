<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS} {V}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS}']);

        DB::table('machines')
            ->whereRaw("LENGTH(serial_number) = 14 AND SUBSTR(serial_number, 13, 1) = ' '")
            ->update(['serial_number' => DB::raw('SUBSTR(serial_number, 1, 12)')]);
    }

    public function down(): void
    {

        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS} {V}']);
    }
};
