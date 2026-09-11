<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_reports', function (Blueprint $table) {
            $table->enum('type', ['full_body', 'meridian', 'multidimensional'])->nullable()->after('file');
        });
    }

    public function down(): void
    {
        Schema::table('health_reports', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
