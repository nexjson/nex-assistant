<?php

use App\Models\User;
use App\Services\Whatsapp\WhatsappMessenger;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;

test('sending a message delegates to the whatsapp messenger', function () {
    $user = User::factory()->create();

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('123@c.us', 'Hello!')
        ->andReturn(['id' => 'msg-1', 'status' => 'sent']);

    $response = $this->actingAs($user)->post(route('whatsapp.chats.messages.store', ['chatId' => '123@c.us']), [
        'body' => 'Hello!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('sending a message requires a body', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('whatsapp.chats.messages.store', ['chatId' => '123@c.us']), [
        'body' => '',
    ]);

    $response->assertSessionHasErrors('body');
});

test('a sidecar failure is surfaced as a validation error', function () {
    $user = User::factory()->create();

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->andThrow(new SidecarException('Sidecar transport error: connection refused'));

    $response = $this->actingAs($user)->post(route('whatsapp.chats.messages.store', ['chatId' => '123@c.us']), [
        'body' => 'Hello!',
    ]);

    $response->assertSessionHasErrors('body');
});
