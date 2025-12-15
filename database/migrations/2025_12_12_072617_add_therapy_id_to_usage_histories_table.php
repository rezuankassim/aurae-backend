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
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->foreignId('therapy_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->dropForeign(['therapy_id']);
            $table->dropColumn('therapy_id');
        });
    }
};
