<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // For MySQL, we need to modify the column to be nullable
            DB::statement('ALTER TABLE lunar_transactions MODIFY order_id BIGINT UNSIGNED NULL');
        } else {
            // For SQLite and others
            Schema::table('lunar_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // First set any NULL order_id to 0 or delete those records
            DB::statement('DELETE FROM lunar_transactions WHERE order_id IS NULL');
            DB::statement('ALTER TABLE lunar_transactions MODIFY order_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('lunar_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable(false)->change();
            });
        }
    }
};
