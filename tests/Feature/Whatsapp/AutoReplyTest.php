<?php

use App\Jobs\GenerateWhatsappAutoReply;
use App\Models\WhatsappAiSetting;
use App\Services\OpenRouter\OpenRouterClient;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Support\Facades\Bus;
use Kstmostofa\LaravelWhatsApp\Events\Web\MessageReceived;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

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

    $this->mock(OpenRouterClient::class)->shouldNotReceive('chat');
    $this->mock(WhatsappMessenger::class)->shouldNotReceive('sendText');

    (new GenerateWhatsappAutoReply('123@c.us', 'hello'))->handle(
        app(OpenRouterClient::class),
        app(WhatsappMessenger::class),
    );
});

test('the job generates and sends a reply when enabled', function () {
    WhatsappAiSetting::current()->update([
        'auto_reply_enabled' => true,
        'openrouter_model' => 'anthropic/claude-sonnet-4.5',
        'openrouter_api_key' => 'sk-or-test',
    ]);

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-1',
        'direction' => 'inbound',
        'chat_id' => '123@c.us',
        'from_id' => '123@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'hello',
        'status' => 'received',
        'wa_timestamp' => now(),
    ]);

    $this->mock(OpenRouterClient::class)
        ->shouldReceive('chat')
        ->once()
        ->withArgs(function (array $messages, string $model, string $apiKey) {
            return $model === 'anthropic/claude-sonnet-4.5'
                && $apiKey === 'sk-or-test'
                && $messages[0]['role'] === 'system'
                && end($messages)['content'] === 'hello';
        })
        ->andReturn('Hi! How can I help?');

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('123@c.us', 'Hi! How can I help?');

    (new GenerateWhatsappAutoReply('123@c.us', 'hello'))->handle(
        app(OpenRouterClient::class),
        app(WhatsappMessenger::class),
    );
});
