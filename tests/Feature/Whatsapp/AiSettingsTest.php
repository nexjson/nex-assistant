<?php

use App\Models\User;
use App\Models\WhatsappAiSetting;
use Inertia\Testing\AssertableInertia as Assert;

test('ai settings page shows whether a key is configured without exposing it', function () {
    $user = User::factory()->create();

    WhatsappAiSetting::current()->update([
        'openrouter_api_key' => 'sk-or-secret',
        'openrouter_model' => 'anthropic/claude-sonnet-4.5',
    ]);

    $response = $this->actingAs($user)->get(route('whatsapp.ai-settings.show'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Whatsapp/AiSettings')
        ->where('settings.hasApiKey', true)
        ->where('settings.openrouterModel', 'anthropic/claude-sonnet-4.5')
        ->missing('settings.openrouterApiKey')
    );
    $response->assertDontSee('sk-or-secret');
});

test('settings can be updated including a new api key', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('whatsapp.ai-settings.update'), [
        'auto_reply_enabled' => true,
        'openrouter_model' => 'openai/gpt-4o-mini',
        'openrouter_api_key' => 'sk-or-new-key',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $settings = WhatsappAiSetting::current();
    expect($settings->auto_reply_enabled)->toBeTrue();
    expect($settings->openrouter_model)->toBe('openai/gpt-4o-mini');
    expect($settings->openrouter_api_key)->toBe('sk-or-new-key');
});

test('leaving the api key blank keeps the existing key', function () {
    $user = User::factory()->create();

    WhatsappAiSetting::current()->update(['openrouter_api_key' => 'sk-or-existing']);

    $response = $this->actingAs($user)->put(route('whatsapp.ai-settings.update'), [
        'auto_reply_enabled' => false,
        'openrouter_model' => 'anthropic/claude-sonnet-4.5',
        'openrouter_api_key' => null,
    ]);

    $response->assertSessionHasNoErrors();
    expect(WhatsappAiSetting::current()->openrouter_api_key)->toBe('sk-or-existing');
});

test('enabling auto-reply without any api key fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('whatsapp.ai-settings.update'), [
        'auto_reply_enabled' => true,
        'openrouter_model' => 'anthropic/claude-sonnet-4.5',
        'openrouter_api_key' => null,
    ]);

    $response->assertSessionHasErrors('openrouter_api_key');
    expect(WhatsappAiSetting::current()->auto_reply_enabled)->toBeFalse();
});
