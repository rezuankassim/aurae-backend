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
        Schema::table('lunar_discounts', function (Blueprint $table) {
            $table->boolean('enabled')->default(false)->after('stop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lunar_discounts', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};
