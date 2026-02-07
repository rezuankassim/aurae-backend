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
            $table->longText('terms_and_conditions_content')->nullable();
            $table->longText('terms_and_conditions_html')->nullable();
            $table->longText('privacy_policy_content')->nullable();
            $table->longText('privacy_policy_html')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'terms_and_conditions_content',
                'terms_and_conditions_html',
                'privacy_policy_content',
                'privacy_policy_html',
            ]);
        });
    }
};
