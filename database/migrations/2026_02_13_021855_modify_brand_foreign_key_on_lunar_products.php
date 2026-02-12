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
        Schema::table('lunar_products', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['brand_id']);

            // Re-add the foreign key with SET NULL on delete
            $table->foreign('brand_id')
                ->references('id')
                ->on('lunar_brands')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lunar_products', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['brand_id']);

            // Re-add the original foreign key (without nullOnDelete)
            $table->foreign('brand_id')
                ->references('id')
                ->on('lunar_brands');
        });
    }
};
