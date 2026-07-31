<?php

use App\Models\User;
use App\Services\Whatsapp\WhatsappMessenger;
use Inertia\Testing\AssertableInertia as Assert;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

$seedMessages = function (): void {
    foreach (range(1, 55) as $i) {
        WaMessage::create([
            'backend' => 'web',
            'session_id' => 'main',
            'wa_message_id' => "msg-{$i}",
            'direction' => 'inbound',
            'chat_id' => '123@c.us',
            'from_id' => '123@c.us',
            'to_id' => 'me',
            'type' => 'text',
            'body' => "message {$i}",
            'status' => 'received',
            'wa_timestamp' => now()->subMinutes(60 - $i),
        ]);
    }
};

test('the thread page exposes a next cursor when there are older messages', function () use ($seedMessages) {
    $user = User::factory()->create();

    $seedMessages();

    $response = $this->actingAs($user)->get(route('whatsapp.chats.show', ['chatId' => '123@c.us']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Whatsapp/Thread')
        ->where('messages.data', fn ($data) => count($data) === 50)
        ->where('messages.next_cursor', fn ($cursor) => filled($cursor))
    );
});

test('requesting with a cursor returns the next page of older messages without overlap', function () use ($seedMessages) {
    $user = User::factory()->create();

    $seedMessages();

    $first = $this->actingAs($user)->get(route('whatsapp.chats.show', ['chatId' => '123@c.us']));
    $cursor = $first->viewData('page')['props']['messages']['next_cursor'];

    $second = $this->actingAs($user)->get(route('whatsapp.chats.show', ['chatId' => '123@c.us', 'cursor' => $cursor]));

    $firstBodies = collect($first->viewData('page')['props']['messages']['data'])->pluck('body');
    $secondBodies = collect($second->viewData('page')['props']['messages']['data'])->pluck('body');

    expect($secondBodies)->toHaveCount(5);
    expect($firstBodies->intersect($secondBodies))->toBeEmpty();
});

test('the thread page is labelled with the contact name when the sidecar knows one', function () {
    $user = User::factory()->create();

    WaMessage::create([
        'backend' => 'web', 'session_id' => 'main', 'wa_message_id' => 'msg-1',
        'direction' => 'inbound', 'chat_id' => '123@c.us', 'from_id' => '123@c.us', 'to_id' => 'me',
        'type' => 'text', 'body' => 'hi', 'status' => 'received', 'wa_timestamp' => now(),
    ]);

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('chats')
        ->once()
        ->andReturn([
            ['id' => '123@c.us', 'name' => 'Jane Doe', 'isGroup' => false],
        ]);

    $response = $this->actingAs($user)->get(route('whatsapp.chats.show', ['chatId' => '123@c.us']));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Whatsapp/Thread')
        ->where('contactName', 'Jane Doe')
    );
});
