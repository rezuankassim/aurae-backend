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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('apk_file_path')->nullable();
            $table->string('apk_version')->nullable();
            $table->bigInteger('apk_file_size')->nullable();
            $table->text('apk_release_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['apk_file_path', 'apk_version', 'apk_file_size', 'apk_release_notes']);
        });
    }
};
