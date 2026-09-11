<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunar_products', function (Blueprint $table) {

            $table->dropForeign(['brand_id']);

            $table->foreign('brand_id')
                ->references('id')
                ->on('lunar_brands')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lunar_products', function (Blueprint $table) {

            $table->dropForeign(['brand_id']);

            $table->foreign('brand_id')
                ->references('id')
                ->on('lunar_brands');
        });
    }
};
