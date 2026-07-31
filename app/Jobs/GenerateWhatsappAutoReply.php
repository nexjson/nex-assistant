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

            if ($this->looksLikeLeakedToolCall($response->text)) {
                Log::warning('WhatsApp auto-reply discarded: model emitted an unexecuted tool-call as plain text instead of a real reply', [
                    'chat_id' => $this->chatId,
                    'model' => $settings->openrouter_model,
                    'text' => $response->text,
                ]);

                return;
            }

            $messenger->sendText($this->chatId, $response->text);
        } catch (AiException|SidecarException $e) {
            Log::warning('WhatsApp auto-reply failed', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Some models don't reliably support native tool calling and instead
     * write out a text imitation of a function call (e.g.
     * `<function=SearchWhatsappHistoryTool>{...}</function>`) as their final
     * answer. That syntax must never reach the end user on WhatsApp.
     */
    private function looksLikeLeakedToolCall(string $text): bool
    {
        return (bool) preg_match('/<\/?function[\s=]/i', $text);
    }
}
