<?php

namespace App\Jobs;

use App\Ai\Agents\WhatsappAssistant;
use App\Models\WhatsappAiSetting;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Laravel\Ai\Ai;
use Laravel\Ai\Exceptions\AiException;

class GenerateWhatsappAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly string $body,
    ) {}

    public function handle(WhatsappMessenger $messenger): void
    {
        $settings = WhatsappAiSetting::current();

        if (! $settings->auto_reply_enabled || ! $settings->openrouter_api_key) {
            return;
        }

        // The OpenRouter key is stored per-installation in the database and
        // can change at any time, but the AI SDK caches resolved providers
        // for the lifetime of the (long-running) queue worker process.
        // Forget the cached instance so the key we set below is actually
        // picked up instead of a stale one from an earlier job.
        Ai::forgetInstance('openrouter');
        config(['ai.providers.openrouter.key' => $settings->openrouter_api_key]);

        try {
            $response = (new WhatsappAssistant($settings, $this->chatId, $this->body))
                ->prompt($this->body, provider: 'openrouter', model: $settings->openrouter_model);

            $messenger->sendText($this->chatId, $response->text);
        } catch (AiException|SidecarException $e) {
            Log::warning('WhatsApp auto-reply failed', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
