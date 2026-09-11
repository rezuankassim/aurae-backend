<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('tablet_apk_file_path')->nullable();
            $table->string('tablet_apk_version')->nullable();
            $table->bigInteger('tablet_apk_file_size')->nullable();
            $table->text('tablet_apk_release_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['tablet_apk_file_path', 'tablet_apk_version', 'tablet_apk_file_size', 'tablet_apk_release_notes']);
        });
    }
};
