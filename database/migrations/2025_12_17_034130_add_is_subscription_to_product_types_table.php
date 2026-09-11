<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunar_product_types', function (Blueprint $table) {
            $table->boolean('is_subscription')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('lunar_product_types', function (Blueprint $table) {
            $table->dropColumn('is_subscription');
        });
    }
};
