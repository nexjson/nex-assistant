<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListWhatsappConversationsTool;
use App\Mcp\Tools\SendWhatsappMessageTool;
use App\Mcp\Tools\ShowWhatsappConversationTool;
use App\Mcp\Tools\WhatsappSessionStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Whatsapp Server')]
#[Version('0.0.1')]
#[Instructions('Provides access to this application\'s WhatsApp integration: read conversations and messages, check the pairing session status, and send text messages.')]
class WhatsappServer extends Server
{
    protected array $tools = [
        WhatsappSessionStatusTool::class,
        ListWhatsappConversationsTool::class,
        ShowWhatsappConversationTool::class,
        SendWhatsappMessageTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
