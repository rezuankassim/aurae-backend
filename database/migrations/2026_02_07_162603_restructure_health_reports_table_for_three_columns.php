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
        Schema::table('health_reports', function (Blueprint $table) {
            // Drop the old columns
            $table->dropColumn(['file', 'type']);
        });

        Schema::table('health_reports', function (Blueprint $table) {
            // Add the three report file columns
            $table->string('full_body_file')->nullable()->after('id');
            $table->string('meridian_file')->nullable()->after('full_body_file');
            $table->string('multidimensional_file')->nullable()->after('meridian_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_reports', function (Blueprint $table) {
            $table->dropColumn(['full_body_file', 'meridian_file', 'multidimensional_file']);
        });

        Schema::table('health_reports', function (Blueprint $table) {
            $table->string('file')->after('id');
            $table->enum('type', ['full_body', 'meridian', 'multidimensional'])->nullable()->after('file');
        });
    }
};
