<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\UpdateAiSettingRequest;
use App\Models\WhatsappAiSetting;
use Illuminate\Http\RedirectResponse;
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

    public function update(UpdateAiSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        WhatsappAiSetting::current()->update([
            'auto_reply_enabled' => $validated['auto_reply_enabled'],
            'openrouter_model' => $validated['openrouter_model'],
            'openrouter_api_key' => $request->resolvedApiKey(),
        ]);

        return back();
    }
}
