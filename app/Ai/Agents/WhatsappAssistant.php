<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchWhatsappHistoryTool;
use App\Models\WhatsappAiSetting;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class WhatsappAssistant implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(
        private readonly WhatsappAiSetting $settings,
        private readonly string $chatId,
        private readonly string $triggeringMessage,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $prompt = 'You are a helpful WhatsApp assistant replying on behalf of the account owner. Keep replies concise and friendly. Always reply in the same language the user wrote their most recent message in, including regional Indonesian languages such as Sundanese or Javanese — do not switch to Indonesian or English unless the user did.';

        $profile = array_filter([
            $this->settings->owner_name ? "Name: {$this->settings->owner_name}" : null,
            $this->settings->owner_address ? "Address: {$this->settings->owner_address}" : null,
            $this->settings->current_activity ? "Current activity/status: {$this->settings->current_activity}" : null,
        ]);

        if ($profile !== []) {
            $prompt .= " Here is what you know about the account owner, use it to answer questions about them (e.g. their name, address, or what they're doing right now):\n"
                .implode("\n", $profile);
        }

        return $prompt;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * `prompt()` always appends the triggering message as the newest user
     * turn itself, so it must not also appear here. Whether it does depends
     * on listener ordering: the inbound message may or may not have been
     * persisted to the database yet by the time this job runs.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        $history = WaMessage::query()
            ->where('backend', 'web')
            ->where(fn ($query) => $query->where('from_id', $this->chatId)->orWhere('to_id', $this->chatId))
            ->orderByDesc('id')
            ->limit(21)
            ->get()
            ->reverse()
            ->values();

        $last = $history->last();

        if ($last && $last->direction !== 'outbound' && $last->body === $this->triggeringMessage) {
            $history = $history->slice(0, -1);
        }

        return $history
            ->slice(-20)
            ->map(fn (WaMessage $message) => new Message(
                $message->direction === 'outbound' ? 'assistant' : 'user',
                (string) $message->body,
            ))
            ->values()
            ->all();
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchWhatsappHistoryTool($this->chatId),
        ];
    }
}
