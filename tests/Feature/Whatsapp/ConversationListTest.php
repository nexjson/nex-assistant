<?php

use App\Models\User;
use App\Services\Whatsapp\WhatsappMessenger;
use Inertia\Testing\AssertableInertia as Assert;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

test('conversation list groups messages by chat partner and shows the latest one', function () {
    $user = User::factory()->create();

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-1',
        'direction' => 'inbound',
        'chat_id' => '111@c.us',
        'from_id' => '111@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'Hello there',
        'status' => 'received',
        'wa_timestamp' => now()->subMinutes(10),
    ]);

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-2',
        'direction' => 'outbound',
        'chat_id' => 'me',
        'from_id' => 'me',
        'to_id' => '111@c.us',
        'type' => 'text',
        'body' => 'Hi, how can I help?',
        'status' => 'sent',
        'wa_timestamp' => now(),
    ]);

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-3',
        'direction' => 'inbound',
        'chat_id' => '222@c.us',
        'from_id' => '222@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'Another chat',
        'status' => 'received',
        'wa_timestamp' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($user)->get(route('whatsapp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Whatsapp/Index')
        ->has('conversations', 2)
        ->where('conversations.0.chatId', '111@c.us')
        ->where('conversations.0.lastBody', 'Hi, how can I help?')
        ->where('conversations.1.chatId', '222@c.us')
    );
});

test('conversations are labelled with the contact name when the sidecar knows one', function () {
    $user = User::factory()->create();

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-1',
        'direction' => 'inbound',
        'chat_id' => '111@c.us',
        'from_id' => '111@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'Hello there',
        'status' => 'received',
        'wa_timestamp' => now(),
    ]);

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('chats')
        ->once()
        ->andReturn([
            ['id' => '111@c.us', 'name' => 'Jane Doe', 'isGroup' => false],
        ])
        ->shouldReceive('state')
        ->once()
        ->andReturn(['id' => 'main', 'status' => 'ready']);

    $response = $this->actingAs($user)->get(route('whatsapp.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Whatsapp/Index')
        ->where('conversations.0.chatId', '111@c.us')
        ->where('conversations.0.contactName', 'Jane Doe')
    );
});
