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
        Schema::create('whatsapp_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('openrouter_model')->default('anthropic/claude-sonnet-4.5');
            $table->text('openrouter_api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_ai_settings');
    }
};
