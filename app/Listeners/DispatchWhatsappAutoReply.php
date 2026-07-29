<?php

namespace App\Listeners;

use App\Jobs\GenerateWhatsappAutoReply;
use Kstmostofa\LaravelWhatsApp\Events\Web\MessageReceived;

class DispatchWhatsappAutoReply
{
    public function handle(MessageReceived $event): void
    {
        if ($event->fromMe()) {
            return;
        }

        $from = $event->from();
        $body = $event->body();

        if (! $from || ! $body) {
            return;
        }

        GenerateWhatsappAutoReply::dispatch($from, $body);
    }
}
