<?php

namespace App\Mcp\Tools;

use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Shows the current WhatsApp pairing/session status (e.g. ready, qr, unreachable).')]
#[IsReadOnly]
class WhatsappSessionStatusTool extends Tool
{
    public function __construct(private readonly WhatsappMessenger $messenger) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        return Response::structured($this->messenger->state());
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
            'id' => $schema->string()->description('The WhatsApp session id.')->required(),
            'status' => $schema->string()->description('The session status, e.g. "ready", "qr", "unreachable".')->required(),
        ];
    }
}
