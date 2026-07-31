<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;
use Kstmostofa\LaravelWhatsApp\Web\SidecarManager;

/**
 * Starts the WhatsApp sidecar only if it isn't already running. Unlike
 * `whatsapp:sidecar:start`, this is safe to call every time `composer run dev`
 * boots, since it won't fail just because the sidecar survived from a
 * previous run.
 */
#[Signature('whatsapp:sidecar:ensure')]
#[Description('Start the WhatsApp sidecar if it is not already running')]
class WhatsappSidecarEnsureCommand extends Command
{
    public function handle(SidecarManager $manager): int
    {
        if ($manager->isRunning()) {
            $this->info("Sidecar already running (pid {$manager->pid()}).");

            return self::SUCCESS;
        }

        if (! $manager->isInstalled()) {
            $this->warn('Sidecar not installed. Run `php artisan whatsapp:sidecar:install` first.');

            return self::SUCCESS;
        }

        try {
            $pid = $manager->start();
        } catch (SidecarException $e) {
            $this->warn("Could not start sidecar: {$e->getMessage()}");

            return self::SUCCESS;
        }

        $this->info("Sidecar started (pid {$pid}). Listening on http://{$manager->host()}:{$manager->port()}");

        return self::SUCCESS;
    }
}
