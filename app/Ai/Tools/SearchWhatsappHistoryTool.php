<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the assistant look further back than the recent messages it is
 * already given when it needs older context. Scoped to a single chat_id so
 * a reply generated for one conversation can never read another.
 */
class SearchWhatsappHistoryTool implements Tool
{
    public function __construct(private readonly string $chatId) {}

    public function description(): Stringable|string
    {
        return "Search this WhatsApp conversation's older message history for a keyword or phrase. Use this only when you need context from further back than the recent messages already provided.";
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request['query'];
        $limit = $request['limit'] ?? 10;

        $messages = WaMessage::query()
            ->where('backend', 'web')
            ->where(fn ($q) => $q->where('from_id', $this->chatId)->orWhere('to_id', $this->chatId))
            ->where('body', 'like', '%'.$query.'%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return "No messages matching \"{$query}\" were found in this conversation.";
        }

        return $messages
            ->map(fn (WaMessage $message) => sprintf(
                '[%s] %s: %s',
                $message->wa_timestamp?->toIso8601String() ?? 'unknown time',
                $message->direction === 'outbound' ? 'assistant' : 'user',
                $message->body,
            ))
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The keyword or phrase to search for.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of matching messages to return.')
                ->min(1)
                ->max(30)
                ->default(10),
        ];
    }
}
