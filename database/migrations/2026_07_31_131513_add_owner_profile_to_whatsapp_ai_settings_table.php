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
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('openrouter_api_key');
            $table->string('owner_address')->nullable()->after('owner_name');
            $table->string('current_activity')->nullable()->after('owner_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'owner_address', 'current_activity']);
        });
    }
};
