<?php

use App\Events\WhatsAppSessionStatusChanged;
use Illuminate\Support\Facades\Event;
use Kstmostofa\LaravelWhatsApp\Events\Web\Disconnected;
use Kstmostofa\LaravelWhatsApp\Events\Web\SessionReady;

test('session ready dispatches a broadcastable status changed event', function () {
    Event::fake([WhatsAppSessionStatusChanged::class]);

    event(new SessionReady('main'));

    Event::assertDispatched(
        WhatsAppSessionStatusChanged::class,
        fn (WhatsAppSessionStatusChanged $event) => $event->sessionId === 'main' && $event->status === 'ready',
    );
});

test('disconnected dispatches a broadcastable status changed event with the reason', function () {
    Event::fake([WhatsAppSessionStatusChanged::class]);

    event(new Disconnected('main', ['reason' => 'LOGOUT']));

    Event::assertDispatched(
        WhatsAppSessionStatusChanged::class,
        fn (WhatsAppSessionStatusChanged $event) => $event->sessionId === 'main'
            && $event->status === 'disconnected'
            && $event->reason === 'LOGOUT',
    );
});
