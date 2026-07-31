<?php

namespace App\Mcp\Tools;

use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Sends a WhatsApp text message to a given chat.')]
#[IsDestructive]
class SendWhatsappMessageTool extends Tool
{
    public function __construct(private readonly WhatsappMessenger $messenger) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string'],
            'body' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $this->messenger->sendText($validated['chat_id'], $validated['body']);
        } catch (SidecarException $e) {
            return Response::error("Could not send message: {$e->getMessage()}");
        }

        return Response::text("Message sent to {$validated['chat_id']}.");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'chat_id' => $schema->string()
                ->description('The WhatsApp chat/contact id to message, e.g. "628123456789@c.us".')
                ->required(),
            'body' => $schema->string()
                ->description('The text message body to send.')
                ->required(),
        ];
    }
}
