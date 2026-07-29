<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class WhatsAppSessionStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        $prefix = config('laravel-whatsapp.broadcasting.channel_prefix', 'whatsapp');

        return new PrivateChannel("{$prefix}.session.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'session.status';
    }

    /**
     * @return array{status: string, reason: ?string, at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
            'at' => now()->toIso8601String(),
        ];
    }
}
