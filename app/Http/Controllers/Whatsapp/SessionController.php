<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;

class SessionController extends Controller
{
    public function show(WhatsappMessenger $messenger): Response
    {
        $state = $messenger->state();

        return Inertia::render('Whatsapp/Pairing', [
            'session' => $state,
            'qrDataUri' => $state['status'] === 'qr' ? $messenger->qr()['qr'] ?? null : null,
        ]);
    }

    public function start(WhatsappMessenger $messenger): RedirectResponse
    {
        try {
            $messenger->start();
        } catch (SidecarException $e) {
            return back()->withErrors(['session' => "Could not start pairing: {$e->getMessage()}"]);
        }

        return back();
    }

    public function destroy(WhatsappMessenger $messenger): RedirectResponse
    {
        try {
            $messenger->destroy();
        } catch (SidecarException $e) {
            return back()->withErrors(['session' => "Could not disconnect: {$e->getMessage()}"]);
        }

        return back();
    }
}
