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
        Schema::table('program_logs', function (Blueprint $table) {
            $table->text('program_error_message')->nullable()->after('program_ended_at');
            $table->boolean('emergency')->default(false)->after('program_error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_logs', function (Blueprint $table) {
            $table->dropColumn(['program_error_message', 'emergency']);
        });
    }
};
