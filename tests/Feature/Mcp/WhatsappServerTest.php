<?php

use App\Mcp\Servers\WhatsappServer;
use App\Mcp\Tools\ListWhatsappConversationsTool;
use App\Mcp\Tools\SendWhatsappMessageTool;
use App\Mcp\Tools\ShowWhatsappConversationTool;
use App\Mcp\Tools\WhatsappSessionStatusTool;
use App\Models\User;
use App\Services\Whatsapp\WhatsappMessenger;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Kstmostofa\LaravelWhatsApp\Models\WaMessage;

test('guests cannot reach the whatsapp mcp server', function () {
    $this->postJson('/mcp/whatsapp', ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => 1])
        ->assertUnauthorized();
});

test('the session status tool reports the current pairing state', function () {
    $user = User::factory()->create();

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('state')
        ->once()
        ->andReturn(['id' => 'main', 'status' => 'ready']);

    $response = WhatsappServer::actingAs($user)->tool(WhatsappSessionStatusTool::class, []);

    $response->assertOk()->assertSee('ready');
});

test('the list conversations tool returns the latest message per chat', function () {
    $user = User::factory()->create();

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-1',
        'direction' => 'inbound',
        'chat_id' => '123@c.us',
        'from_id' => '123@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'hello there',
        'status' => 'received',
        'wa_timestamp' => now(),
    ]);

    $response = WhatsappServer::actingAs($user)->tool(ListWhatsappConversationsTool::class, []);

    $response->assertOk()->assertSee('123@c.us')->assertSee('hello there');
});

test('the show conversation tool returns messages for a chat', function () {
    $user = User::factory()->create();

    WaMessage::create([
        'backend' => 'web',
        'session_id' => 'main',
        'wa_message_id' => 'msg-1',
        'direction' => 'inbound',
        'chat_id' => '123@c.us',
        'from_id' => '123@c.us',
        'to_id' => 'me',
        'type' => 'text',
        'body' => 'hello there',
        'status' => 'received',
        'wa_timestamp' => now(),
    ]);

    $response = WhatsappServer::actingAs($user)->tool(ShowWhatsappConversationTool::class, [
        'chat_id' => '123@c.us',
    ]);

    $response->assertOk()->assertSee('hello there');
});

test('the send message tool delegates to the whatsapp messenger', function () {
    $user = User::factory()->create();

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->with('123@c.us', 'Hello!')
        ->andReturn(['id' => 'msg-1', 'status' => 'sent']);

    $response = WhatsappServer::actingAs($user)->tool(SendWhatsappMessageTool::class, [
        'chat_id' => '123@c.us',
        'body' => 'Hello!',
    ]);

    $response->assertOk()->assertSee('123@c.us');
});

test('the send message tool surfaces a sidecar failure as a tool error', function () {
    $user = User::factory()->create();

    $this->mock(WhatsappMessenger::class)
        ->shouldReceive('sendText')
        ->once()
        ->andThrow(new SidecarException('connection refused'));

    $response = WhatsappServer::actingAs($user)->tool(SendWhatsappMessageTool::class, [
        'chat_id' => '123@c.us',
        'body' => 'Hello!',
    ]);

    $response->assertHasErrors();
});
