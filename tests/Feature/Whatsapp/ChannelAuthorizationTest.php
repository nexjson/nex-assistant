<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// The testing environment defaults BROADCAST_CONNECTION to "null" (phpunit.xml),
// which no-ops all auth checks and never resolves the "reverb" driver. Switch
// the default and re-register the channel so authorization actually runs
// against the same rule declared in routes/channels.php.
beforeEach(function () {
    config(['broadcasting.default' => 'reverb']);

    Broadcast::channel('whatsapp.session.{sessionId}', function (User $user, string $sessionId) {
        return $sessionId === 'main';
    });
});

test('guests cannot authorize the whatsapp session channel', function () {
    $response = $this->post('/broadcasting/auth', [
        'channel_name' => 'private-whatsapp.session.main',
        'socket_id' => '1234.1234',
    ]);

    $response->assertForbidden();
});

test('authenticated users can authorize the whatsapp session channel', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-whatsapp.session.main',
        'socket_id' => '1234.1234',
    ]);

    $response->assertOk();
});
