<script module lang="ts">
    import { index, pairing } from '@/routes/whatsapp';

    export const layout = {
        breadcrumbs: [
            {
                title: 'WhatsApp',
                href: index(),
            },
            {
                title: 'Pairing',
                href: pairing(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import SessionController from '@/actions/App/Http/Controllers/Whatsapp/SessionController';
    import AppHead from '@/components/AppHead.svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { getEcho } from '@/lib/echo';

    let {
        session,
        qrDataUri: initialQrDataUri,
    }: {
        session: { id: string; status: string };
        qrDataUri: string | null;
    } = $props();

    let status = $state(session.status);
    let reason = $state<string | null>(null);
    let qrDataUri = $state(initialQrDataUri);

    $effect(() => {
        const echo = getEcho();
        const channel = echo.private('whatsapp.session.main');

        channel.listen('.qr.generated', (event: { dataUri: string }) => {
            qrDataUri = event.dataUri;
            status = 'qr';
        });

        channel.listen(
            '.session.status',
            (event: { status: string; reason: string | null }) => {
                status = event.status;
                reason = event.reason;

                if (event.status === 'ready') {
                    qrDataUri = null;
                }
            },
        );

        return () => echo.leave('whatsapp.session.main');
    });
</script>

<AppHead title="WhatsApp pairing" />

<div class="flex h-full flex-1 flex-col items-center justify-center gap-6 p-4">
    {#if status === 'ready'}
        <Alert>
            <AlertTitle>Connected</AlertTitle>
            <AlertDescription
                >Your WhatsApp session is paired and ready.</AlertDescription
            >
        </Alert>

        <Form {...SessionController.destroy.form()}>
            {#snippet children({ processing })}
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    {#if processing}<Spinner />{/if}
                    Disconnect
                </Button>
            {/snippet}
        </Form>
    {:else}
        {#if status === 'disconnected' && reason}
            <Alert variant="destructive">
                <AlertTitle>Disconnected</AlertTitle>
                <AlertDescription>{reason}</AlertDescription>
            </Alert>
        {/if}

        {#if qrDataUri}
            <img
                src={qrDataUri}
                alt="Scan this QR code with WhatsApp on your phone"
                class="size-64 rounded-lg border"
            />
            <p class="text-sm text-muted-foreground">
                Open WhatsApp on your phone, go to Linked Devices, and scan this
                code.
            </p>
        {:else}
            <Form {...SessionController.start.form()}>
                {#snippet children({ processing })}
                    <Button type="submit" disabled={processing}>
                        {#if processing}<Spinner />{/if}
                        Start pairing
                    </Button>
                {/snippet}
            </Form>
        {/if}
    {/if}
</div>
