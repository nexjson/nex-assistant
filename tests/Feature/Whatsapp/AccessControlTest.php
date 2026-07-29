<?php

use App\Models\User;

test('guests are redirected to login', function (string $route) {
    $this->get($route)->assertRedirect(route('login'));
})->with([
    fn () => route('whatsapp.index'),
    fn () => route('whatsapp.pairing'),
    fn () => route('whatsapp.chats.show', ['chatId' => '123@c.us']),
]);

test('authenticated verified users can view whatsapp pages', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($route)->assertOk();
})->with([
    fn () => route('whatsapp.index'),
    fn () => route('whatsapp.pairing'),
    fn () => route('whatsapp.chats.show', ['chatId' => '123@c.us']),
]);
