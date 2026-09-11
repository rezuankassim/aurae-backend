<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapies', function (Blueprint $table) {
            $table->foreignId('music_id')->nullable()->constrained('music')->nullOnDelete();
            $table->string('music')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('therapies', function (Blueprint $table) {
            $table->dropForeign(['music_id']);
            $table->dropColumn('music_id');
            $table->text('music')->nullable(false)->change();
        });
    }
};
