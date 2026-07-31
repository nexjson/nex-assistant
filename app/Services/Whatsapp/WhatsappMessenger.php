<?php

namespace App\Services\Whatsapp;

use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;

class WhatsappMessenger
{
    public function __construct(private readonly string $sessionId = 'main') {}

    /**
     * @return array{id: string, status: string, qr: ?string}
     */
    public function start(): array
    {
        return WhatsApp::web($this->sessionId)->start();
    }

    /**
     * @return array{status: string, qr: ?string}
     */
    public function qr(): array
    {
        try {
            return WhatsApp::web($this->sessionId)->qr();
        } catch (SidecarException) {
            return ['status' => 'unreachable', 'qr' => null];
        }
    }

    /**
     * @return array{id: string, status: string}
     */
    public function state(): array
    {
        try {
            return WhatsApp::web($this->sessionId)->state();
        } catch (SidecarException) {
            return ['id' => $this->sessionId, 'status' => 'unreachable'];
        }
    }

    public function destroy(): void
    {
        WhatsApp::web($this->sessionId)->destroy();
    }

    /**
     * List the session's chats (id, display name, group flag, ...), used to
     * resolve a human-readable name for a chat_id. Empty when the sidecar
     * isn't reachable, so callers should treat a missing name as normal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function chats(): array
    {
        try {
            return WhatsApp::web($this->sessionId)->chats();
        } catch (SidecarException) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $to, string $body): array
    {
        return WhatsApp::web($this->sessionId)->messages()->sendText($to, $body);
    }
}
