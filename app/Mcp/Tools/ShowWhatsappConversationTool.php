<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Shows the most recent messages in a WhatsApp conversation.')]
#[IsReadOnly]
class ShowWhatsappConversationTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string'],
            'limit' => ['integer', 'min:1', 'max:100'],
        ]);

        $messages = WaMessage::query()
            ->where('backend', 'web')
            ->where(fn ($query) => $query->where('from_id', $validated['chat_id'])->orWhere('to_id', $validated['chat_id']))
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 20)
            ->get()
            ->reverse()
            ->map(fn (WaMessage $message) => [
                'direction' => $message->direction,
                'body' => $message->body,
                'status' => $message->status,
                'wa_timestamp' => $message->wa_timestamp?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Response::structured(['messages' => $messages]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'chat_id' => $schema->string()
                ->description('The WhatsApp chat/contact id to fetch messages for.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of recent messages to return.')
                ->min(1)
                ->max(100)
                ->default(20),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'messages' => $schema->array()
                ->items($schema->object([
                    'direction' => $schema->string()->description('"inbound" or "outbound".'),
                    'body' => $schema->string()->description('The message text.'),
                    'status' => $schema->string()->description('Delivery status of the message.'),
                    'wa_timestamp' => $schema->string()->description('ISO-8601 timestamp.'),
                ]))
                ->description('Messages in chronological order (oldest first).')
                ->required(),
        ];
    }
}
