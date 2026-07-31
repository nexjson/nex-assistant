<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists WhatsApp conversations with their most recent message.')]
#[IsReadOnly]
class ListWhatsappConversationsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $partnerExpression = "CASE WHEN direction = 'outbound' THEN to_id ELSE from_id END";

        $latestMessageIds = WaMessage::query()
            ->where('backend', 'web')
            ->selectRaw('MAX(id) as id')
            ->groupBy(DB::raw($partnerExpression))
            ->pluck('id');

        $conversations = WaMessage::query()
            ->whereIn('id', $latestMessageIds)
            ->orderByDesc('wa_timestamp')
            ->get()
            ->map(fn (WaMessage $message) => [
                'chat_id' => $message->direction === 'outbound' ? $message->to_id : $message->from_id,
                'last_body' => $message->body,
                'last_at' => $message->wa_timestamp?->toIso8601String(),
                'direction' => $message->direction,
            ])
            ->values()
            ->all();

        return Response::structured(['conversations' => $conversations]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'conversations' => $schema->array()
                ->items($schema->object([
                    'chat_id' => $schema->string()->description('The WhatsApp chat/contact id.'),
                    'last_body' => $schema->string()->description('The body of the most recent message.'),
                    'last_at' => $schema->string()->description('ISO-8601 timestamp of the most recent message.'),
                    'direction' => $schema->string()->description('"inbound" or "outbound".'),
                ]))
                ->description('The list of conversations, most recent first.')
                ->required(),
        ];
    }
}
