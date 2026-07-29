<?php

namespace App\Jobs;

use App\Exceptions\OpenRouterException;
use App\Models\WhatsappAiSetting;
use App\Services\OpenRouter\OpenRouterClient;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

class GenerateWhatsappAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly string $body,
    ) {}

    public function handle(OpenRouterClient $openRouter, WhatsappMessenger $messenger): void
    {
        $settings = WhatsappAiSetting::current();

        if (! $settings->auto_reply_enabled || ! $settings->openrouter_api_key) {
            return;
        }

        $messages = $this->buildConversation();

        try {
            $reply = $openRouter->chat($messages, $settings->openrouter_model, $settings->openrouter_api_key);
            $messenger->sendText($this->chatId, $reply);
        } catch (OpenRouterException|SidecarException $e) {
            Log::warning('WhatsApp auto-reply failed', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildConversation(): array
    {
        $history = WaMessage::query()
            ->where('backend', 'web')
            ->where(fn ($query) => $query->where('from_id', $this->chatId)->orWhere('to_id', $this->chatId))
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn (WaMessage $message) => [
                'role' => $message->direction === 'outbound' ? 'assistant' : 'user',
                'content' => (string) $message->body,
            ])
            ->values()
            ->all();

        // The inbound message that triggered this job may not have been
        // persisted yet depending on listener ordering — ensure it's present.
        $last = end($history);
        if ($last === false || $last['role'] !== 'user' || $last['content'] !== $this->body) {
            $history[] = ['role' => 'user', 'content' => $this->body];
        }

        return [
            [
                'role' => 'system',
                'content' => 'You are a helpful WhatsApp assistant replying on behalf of the account owner. Keep replies concise and friendly.',
            ],
            ...$history,
        ];
    }
}
