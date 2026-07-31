<?php

use App\Ai\Agents\WhatsappAssistant;
use App\Ai\Tools\SearchWhatsappHistoryTool;
use App\Jobs\GenerateWhatsappAutoReply;
use App\Models\WhatsappAiSetting;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Support\Facades\Bus;
use Kstmostofa\LaravelWhatsApp\Events\Web\MessageReceived;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;
use Laravel\Ai\Tools\Request;

test('an inbound message dispatches the auto-reply job', function () {
    Bus::fake([GenerateWhatsappAutoReply::class]);

    event(new MessageReceived('main', [
        'message' => ['id' => 'msg-1', 'from' => '123@c.us', 'to' => 'me', 'body' => 'hello', 'fromMe' => false, 'timestamp' => time()],
    ]));

    Bus::assertDispatched(
        GenerateWhatsappAutoReply::class,
        fn (GenerateWhatsappAutoReply $job) => $job->chatId === '123@c.us' && $job->body === 'hello',
    );
});

test('an outbound (self-sent) message does not dispatch the auto-reply job', function () {
    Bus::fake([GenerateWhatsappAutoReply::class]);

    event(new MessageReceived('main', [
        'message' => ['id' => 'msg-2', 'from' => 'me', 'to' => '123@c.us', 'body' => 'hi there', 'fromMe' => true, 'timestamp' => time()],
    ]));

    Bus::assertNotDispatched(GenerateWhatsappAutoReply::class);
});

test('the job does nothing when auto-reply is disabled', function () {
    WhatsappAiSetting::current()->update(['auto_reply_enabled' => false]);

    WhatsappAssistant::fake();
    $this->mock(WhatsappMessenger::class)->shouldNotReceive('sendText');

    (new GenerateWhatsappAutoReply('123@c.us', 'hello'))->handle(app(WhatsappMessenger::class));

    WhatsappAssistant::assertNeverPrompted();
});

test('the job generates and sends a reply when enabled', function () {
    WhatsappAiSetting::current()->update([
        'auto_reply_enabled' => true,
        'openrouter_model' => 'anthropic/claude-sonnet-4.5',
        'openrouter_api_key' => 'sk-or-test',
    ]);

    WhatsappAssistant::fake(['Hi! How can I help?']);

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('123@c.us', 'Hi! How can I help?');

    (new GenerateWhatsappAutoReply('123@c.us', 'hello'))->handle(app(WhatsappMessenger::class));

    WhatsappAssistant::assertPrompted('hello');
});

test('the instructions include the owner profile when configured', function () {
    $settings = tap(WhatsappAiSetting::current())->update([
        'owner_name' => 'Hilman',
        'owner_address' => 'Jakarta, Indonesia',
        'current_activity' => 'In a meeting until 3 PM',
    ]);

    $instructions = (string) (new WhatsappAssistant($settings, '123@c.us', 'where are you right now?'))->instructions();

    expect($instructions)
        ->toContain('Hilman')
        ->toContain('Jakarta, Indonesia')
        ->toContain('In a meeting until 3 PM');
});

test('the instructions omit the profile section when nothing is configured', function () {
    $settings = WhatsappAiSetting::current();

    $instructions = (string) (new WhatsappAssistant($settings, '123@c.us', 'hi'))->instructions();

    expect($instructions)->not->toContain('Here is what you know about the account owner');
});

test('conversation history excludes the message already persisted for the current turn', function () {
    $settings = WhatsappAiSetting::current();

    WaMessage::create([
        'backend' => 'web', 'session_id' => 'main', 'wa_message_id' => 'msg-1',
        'direction' => 'inbound', 'chat_id' => '123@c.us', 'from_id' => '123@c.us', 'to_id' => 'me',
        'type' => 'text', 'body' => 'earlier question', 'status' => 'received', 'wa_timestamp' => now()->subMinute(),
    ]);

    // Already persisted by the listener before this job ran.
    WaMessage::create([
        'backend' => 'web', 'session_id' => 'main', 'wa_message_id' => 'msg-2',
        'direction' => 'inbound', 'chat_id' => '123@c.us', 'from_id' => '123@c.us', 'to_id' => 'me',
        'type' => 'text', 'body' => 'hello', 'status' => 'received', 'wa_timestamp' => now(),
    ]);

    $messages = (new WhatsappAssistant($settings, '123@c.us', 'hello'))->messages();

    expect($messages)->toHaveCount(1);
    expect($messages[0]->content)->toBe('earlier question');
});

test('the search history tool only returns messages from its own chat', function () {
    WaMessage::create([
        'backend' => 'web', 'session_id' => 'main', 'wa_message_id' => 'msg-1',
        'direction' => 'inbound', 'chat_id' => '123@c.us', 'from_id' => '123@c.us', 'to_id' => 'me',
        'type' => 'text', 'body' => 'my invoice number is 42', 'status' => 'received', 'wa_timestamp' => now(),
    ]);

    WaMessage::create([
        'backend' => 'web', 'session_id' => 'main', 'wa_message_id' => 'msg-2',
        'direction' => 'inbound', 'chat_id' => '999@c.us', 'from_id' => '999@c.us', 'to_id' => 'me',
        'type' => 'text', 'body' => 'my invoice number is 99', 'status' => 'received', 'wa_timestamp' => now(),
    ]);

    $result = (string) (new SearchWhatsappHistoryTool('123@c.us'))
        ->handle(new Request(['query' => 'invoice']));

    expect($result)->toContain('42')->not->toContain('99');
});
