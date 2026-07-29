<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAiSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingController extends Controller
{
    public function show(): Response
    {
        $settings = WhatsappAiSetting::current();

        return Inertia::render('Whatsapp/AiSettings', [
            'settings' => [
                'autoReplyEnabled' => $settings->auto_reply_enabled,
                'openrouterModel' => $settings->openrouter_model,
                'hasApiKey' => filled($settings->openrouter_api_key),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_reply_enabled' => ['required', 'boolean'],
            'openrouter_model' => ['required', 'string', 'max:255'],
            'openrouter_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = WhatsappAiSetting::current();

        $apiKey = filled($validated['openrouter_api_key'] ?? null)
            ? $validated['openrouter_api_key']
            : $settings->openrouter_api_key;

        if ($validated['auto_reply_enabled'] && blank($apiKey)) {
            throw ValidationException::withMessages([
                'openrouter_api_key' => 'An OpenRouter API key is required to enable auto-reply.',
            ]);
        }

        $settings->update([
            'auto_reply_enabled' => $validated['auto_reply_enabled'],
            'openrouter_model' => $validated['openrouter_model'],
            'openrouter_api_key' => $apiKey,
        ]);

        return back();
    }
}
