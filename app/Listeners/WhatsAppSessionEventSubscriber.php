<?php

namespace App\Listeners;

use App\Events\WhatsAppSessionStatusChanged;
use Illuminate\Events\Dispatcher;
use Kstmostofa\LaravelWhatsApp\Events\Web\Disconnected;
use Kstmostofa\LaravelWhatsApp\Events\Web\SessionReady;

/**
 * SessionReady/Disconnected are plain local events in the package (not
 * broadcastable), so we re-dispatch our own broadcastable event to keep
 * the pairing screen updated live over Reverb.
 */
class WhatsAppSessionEventSubscriber
{
    public function handleSessionReady(SessionReady $event): void
    {
        WhatsAppSessionStatusChanged::dispatch($event->sessionId, 'ready');
    }

    public function handleDisconnected(Disconnected $event): void
    {
        WhatsAppSessionStatusChanged::dispatch($event->sessionId, 'disconnected', $event->reason());
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SessionReady::class, [self::class, 'handleSessionReady']);
        $events->listen(Disconnected::class, [self::class, 'handleDisconnected']);
    }
}
