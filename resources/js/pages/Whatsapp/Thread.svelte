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
    import { Form, router } from '@inertiajs/svelte';
    import MessageController from '@/actions/App/Http/Controllers/Whatsapp/MessageController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
    import { getEcho } from '@/lib/echo';
    import { cn } from '@/lib/utils';

    type Message = {
        id: number;
        waMessageId: string | null;
        direction: 'inbound' | 'outbound';
        body: string | null;
        type: string;
        status: string | null;
        ack: number | null;
        waTimestamp: string | null;
    };

    let {
        chatId,
        contactName,
        messages,
    }: {
        chatId: string;
        contactName: string | null;
        messages: { data: Message[]; next_cursor: string | null };
    } = $props();

    let items = $state([...messages.data].reverse());
    let nextCursor = $state(messages.next_cursor);
    let loadingMore = $state(false);

    function loadOlderMessages() {
        if (!nextCursor || loadingMore) {
            return;
        }

        loadingMore = true;

        router.reload({
            data: { cursor: nextCursor },
            only: ['messages'],
            onSuccess: (page) => {
                const older = page.props.messages as {
                    data: Message[];
                    next_cursor: string | null;
                };

                items = [...[...older.data].reverse(), ...items];
                nextCursor = older.next_cursor;
            },
            onFinish: () => {
                loadingMore = false;
            },
        });
    }

    $effect(() => {
        const echo = getEcho();
        const channel = echo.private('whatsapp.session.main');

        channel.listen(
            '.message.received',
            (event: { message: Record<string, unknown> }) => {
                const message = event.message;
                const fromMe = Boolean(message.fromMe);
                const otherParty = String(fromMe ? message.to : message.from);

                if (otherParty !== chatId) {
                    return;
                }

                const waMessageId =
                    typeof message.id === 'string' ? message.id : null;

                if (
                    waMessageId &&
                    items.some((item) => item.waMessageId === waMessageId)
                ) {
                    return;
                }

                items.push({
                    id: Date.now(),
                    waMessageId,
                    direction: fromMe ? 'outbound' : 'inbound',
                    body: typeof message.body === 'string' ? message.body : '',
                    type:
                        typeof message.type === 'string'
                            ? message.type
                            : 'text',
                    status: fromMe ? 'sent' : 'received',
                    ack: null,
                    waTimestamp:
                        typeof message.timestamp === 'number'
                            ? new Date(message.timestamp * 1000).toISOString()
                            : new Date().toISOString(),
                });
            },
        );

        channel.listen('.message.ack', (event: { id: string; ack: number }) => {
            const match = items.find((item) => item.waMessageId === event.id);

            if (match) {
                match.ack = event.ack;
            }
        });

        return () => echo.leave('whatsapp.session.main');
    });
</script>

<AppHead title={contactName ?? chatId} />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    <h1 class="text-lg font-semibold">{contactName ?? chatId}</h1>

    <div
        class="flex flex-1 flex-col gap-2 overflow-y-auto rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
    >
        {#if nextCursor}
            <div class="flex justify-center pb-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={loadingMore}
                    onclick={loadOlderMessages}
                >
                    {#if loadingMore}<Spinner />{/if}
                    Load older messages
                </Button>
            </div>
        {/if}

        {#each items as message (message.id)}
            <div
                class={cn(
                    'flex',
                    message.direction === 'outbound'
                        ? 'justify-end'
                        : 'justify-start',
                )}
            >
                <div
                    class={cn(
                        'max-w-[70%] rounded-2xl px-4 py-2 text-sm',
                        message.direction === 'outbound'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-foreground',
                    )}
                >
                    <p>{message.body}</p>
                    {#if message.direction === 'outbound' && message.status}
                        <Badge variant="outline" class="mt-1"
                            >{message.status}</Badge
                        >
                    {/if}
                </div>
            </div>
        {:else}
            <p class="text-sm text-muted-foreground">No messages yet.</p>
        {/each}
    </div>

    <Form
        {...MessageController.store.form(chatId)}
        resetOnSuccess={['body']}
        class="flex items-end gap-2"
    >
        {#snippet children({ errors, processing })}
            <div class="flex-1">
                <Input
                    id="body"
                    name="body"
                    placeholder="Type a message"
                    autocomplete="off"
                />
                <InputError message={errors.body} />
            </div>
            <Button type="submit" disabled={processing}>
                {#if processing}<Spinner />{/if}
                Send
            </Button>
        {/snippet}
    </Form>
</div>
