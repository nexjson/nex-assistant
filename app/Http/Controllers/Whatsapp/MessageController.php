<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\StoreMessageRequest;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

class MessageController extends Controller
{
    public function show(string $chatId): Response
    {
        $messages = WaMessage::query()
            ->where('backend', 'web')
            ->where(fn ($query) => $query->where('from_id', $chatId)->orWhere('to_id', $chatId))
            ->orderByDesc('id')
            ->cursorPaginate(50)
            ->through(fn (WaMessage $message) => [
                'id' => $message->id,
                'waMessageId' => $message->wa_message_id,
                'direction' => $message->direction,
                'body' => $message->body,
                'type' => $message->type,
                'status' => $message->status,
                'ack' => $message->ack,
                'waTimestamp' => $message->wa_timestamp?->toIso8601String(),
            ]);

        return Inertia::render('Whatsapp/Thread', [
            'chatId' => $chatId,
            'messages' => $messages,
        ]);
    }

    public function store(StoreMessageRequest $request, string $chatId, WhatsappMessenger $messenger): RedirectResponse
    {
        try {
            $messenger->sendText($chatId, $request->validated('body'));
        } catch (SidecarException $e) {
            return back()->withErrors(['body' => "Could not send message: {$e->getMessage()}"]);
        }

        return back();
    }
}
