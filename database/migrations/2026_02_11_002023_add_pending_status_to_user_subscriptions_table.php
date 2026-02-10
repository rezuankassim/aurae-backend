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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Add 'pending' to the status ENUM for MySQL
            DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending'");
        }
        // SQLite doesn't enforce ENUM constraints, so no action needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Revert back to original ENUM (update any 'pending' to 'cancelled' first)
            DB::statement("UPDATE user_subscriptions SET status = 'cancelled' WHERE status = 'pending'");
            DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");
        }
    }
};
