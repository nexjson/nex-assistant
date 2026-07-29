<?php

namespace App\Services\OpenRouter;

use App\Exceptions\OpenRouterException;
use Illuminate\Support\Facades\Http;

class OpenRouterClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, string $model, string $apiKey): string
    {
        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 512,
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new OpenRouterException("OpenRouter request failed: {$message}");
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new OpenRouterException('OpenRouter returned an empty response.');
        }

        return trim($content);
    }
}
