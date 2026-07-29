<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

class ConversationController extends Controller
{
    public function index(WhatsappMessenger $messenger): Response
    {
        // Outbound rows store `from_id` as our own id (see WaMessage docs), so the
        // chat partner is `to_id` for outbound and `from_id` for inbound messages.
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
                'chatId' => $message->direction === 'outbound' ? $message->to_id : $message->from_id,
                'lastBody' => $message->body,
                'lastAt' => $message->wa_timestamp?->toIso8601String(),
                'direction' => $message->direction,
            ]);

        return Inertia::render('Whatsapp/Index', [
            'conversations' => $conversations,
            'session' => $messenger->state(),
        ]);
    }
}
