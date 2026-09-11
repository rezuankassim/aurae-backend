<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {

            DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending'");
        }

    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {

            DB::statement("UPDATE user_subscriptions SET status = 'cancelled' WHERE status = 'pending'");
            DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");
        }
    }
};
