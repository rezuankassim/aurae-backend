<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        DB::table('general_settings')
            ->where('machine_serial_format', 'AUR-{NNNN}')
            ->update(['machine_serial_format' => '{MMMM}{YYYY}{SSSS} {V}']);
    }

    public function down(): void
    {

        DB::table('general_settings')
            ->where('machine_serial_format', '{MMMM}{YYYY}{SSSS} {V}')
            ->update(['machine_serial_format' => 'AUR-{NNNN}']);
    }
};
