<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::create([
            'id' => '01KBDPGMAZAQ1Z1RD13KY5BEPW',
            'status' => 1,
            'uuid' => '86a2a25a-0eea-4f72-a4a5-79dcf01d4973',
            'name' => 'Olive MD v2',
            'started_at' => '20/5/2025',
            'should_end_at' => '21/5/2026',
            'last_used_at' => now(),
            'last_logged_in_at' => now(),
        ]);
    }
}
