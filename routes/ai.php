<?php

use App\Mcp\Servers\WhatsappServer;
use Laravel\Mcp\Facades\Mcp;

// `Mcp::web()` routes are not part of the `web` middleware group by default
// (no session/CSRF), so it must be added explicitly for the session-based
// `auth` guard to see the caller's cookie.
Mcp::web('/mcp/whatsapp', WhatsappServer::class)
    ->middleware(['web', 'auth', 'verified']);
