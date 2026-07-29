<script module lang="ts">
    import { index } from '@/routes/whatsapp';

    export const layout = {
        breadcrumbs: [
            {
                title: 'WhatsApp',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
    import { Badge } from '@/components/ui/badge';
    import { getEcho } from '@/lib/echo';
    import { pairing } from '@/routes/whatsapp';
    import { show } from '@/routes/whatsapp/chats';

    type Conversation = {
        chatId: string;
        lastBody: string | null;
        lastAt: string | null;
        direction: string;
    };

    let {
        conversations,
        session,
    }: {
        conversations: Conversation[];
        session: { id: string; status: string };
    } = $props();

    let items = $state(conversations);

    function upsert(
        chatId: string,
        body: string,
        at: string,
        direction: string,
    ) {
        const existing = items.find((item) => item.chatId === chatId);

        if (existing) {
            existing.lastBody = body;
            existing.lastAt = at;
            existing.direction = direction;
        } else {
            items.unshift({ chatId, lastBody: body, lastAt: at, direction });
        }

        items = [...items].sort((a, b) =>
            (b.lastAt ?? '').localeCompare(a.lastAt ?? ''),
        );
    }

    $effect(() => {
        const echo = getEcho();
        const channel = echo.private('whatsapp.session.main');

        channel.listen(
            '.message.received',
            (event: { message: Record<string, unknown> }) => {
                const message = event.message;
                const fromMe = Boolean(message.fromMe);
                const chatId = String(fromMe ? message.to : message.from);
                const body =
                    typeof message.body === 'string' ? message.body : '';
                const timestamp =
                    typeof message.timestamp === 'number'
                        ? new Date(message.timestamp * 1000).toISOString()
                        : new Date().toISOString();

                upsert(
                    chatId,
                    body,
                    timestamp,
                    fromMe ? 'outbound' : 'inbound',
                );
            },
        );

        return () => echo.leave('whatsapp.session.main');
    });
</script>

<AppHead title="WhatsApp" />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    {#if session.status !== 'ready'}
        <Alert>
            <AlertTitle>Not connected</AlertTitle>
            <AlertDescription>
                <span>Your WhatsApp session isn't paired yet.</span>
                <Link href={pairing()} class="underline">Go to pairing</Link>
            </AlertDescription>
        </Alert>
    {/if}

    <div
        class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border"
    >
        {#each items as conversation (conversation.chatId)}
            <Link
                href={show(conversation.chatId)}
                class="flex items-center justify-between gap-4 p-4 hover:bg-accent"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{conversation.chatId}</p>
                    <p class="truncate text-sm text-muted-foreground">
                        {conversation.direction === 'outbound'
                            ? 'You: '
                            : ''}{conversation.lastBody ?? ''}
                    </p>
                </div>
                {#if conversation.lastAt}
                    <Badge variant="outline"
                        >{new Date(conversation.lastAt).toLocaleString()}</Badge
                    >
                {/if}
            </Link>
        {:else}
            <p class="p-4 text-sm text-muted-foreground">
                No conversations yet.
            </p>
        {/each}
    </div>
</div>
