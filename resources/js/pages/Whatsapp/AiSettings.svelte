<script module lang="ts">
    import { index } from '@/routes/whatsapp';

    export const layout = {
        breadcrumbs: [
            {
                title: 'WhatsApp',
                href: index(),
            },
            {
                title: 'AI auto-reply',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AiSettingController from '@/actions/App/Http/Controllers/Whatsapp/AiSettingController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';

    let {
        settings,
    }: {
        settings: {
            autoReplyEnabled: boolean;
            openrouterModel: string;
            hasApiKey: boolean;
            ownerName: string | null;
            ownerAddress: string | null;
            currentActivity: string | null;
        };
    } = $props();

    let autoReplyEnabled = $state(settings.autoReplyEnabled);
    let openrouterModel = $state(settings.openrouterModel);
    let ownerName = $state(settings.ownerName ?? '');
    let ownerAddress = $state(settings.ownerAddress ?? '');
    let currentActivity = $state(settings.currentActivity ?? '');

    const modelPresets = [
        'anthropic/claude-sonnet-4.5',
        'anthropic/claude-3.5-haiku',
        'openai/gpt-4o-mini',
        'meta-llama/llama-3.1-8b-instruct',
    ];
</script>

<AppHead title="WhatsApp AI auto-reply" />

<div class="flex flex-col gap-6 p-4">
    <Heading
        variant="small"
        title="AI auto-reply"
        description="Automatically reply to incoming WhatsApp messages using an AI model via OpenRouter"
    />

    <Form
        {...AiSettingController.update.form()}
        transform={(data) => ({
            ...data,
            auto_reply_enabled: autoReplyEnabled,
            openrouter_model: openrouterModel,
            owner_name: ownerName,
            owner_address: ownerAddress,
            current_activity: currentActivity,
        })}
        options={{ preserveScroll: true }}
        class="max-w-xl space-y-6"
    >
        {#snippet children({ errors, processing })}
            <div class="flex items-center gap-3">
                <Checkbox
                    id="auto_reply_enabled"
                    bind:checked={autoReplyEnabled}
                />
                <Label for="auto_reply_enabled"
                    >Enable auto-reply for every incoming message</Label
                >
            </div>
            <InputError message={errors.auto_reply_enabled} />

            <div class="grid gap-2">
                <Label for="owner_name">Your name</Label>
                <Input
                    id="owner_name"
                    bind:value={ownerName}
                    placeholder="e.g. Hilman"
                />
                <InputError message={errors.owner_name} />
            </div>

            <div class="grid gap-2">
                <Label for="owner_address">Your address</Label>
                <Input
                    id="owner_address"
                    bind:value={ownerAddress}
                    placeholder="e.g. Jakarta, Indonesia"
                />
                <InputError message={errors.owner_address} />
            </div>

            <div class="grid gap-2">
                <Label for="current_activity">Current activity / status</Label>
                <Input
                    id="current_activity"
                    bind:value={currentActivity}
                    placeholder="e.g. In a meeting until 3 PM"
                />
                <p class="text-sm text-muted-foreground">
                    Update this whenever it changes so the assistant can
                    answer "where are you" or "what are you doing" questions
                    accurately.
                </p>
                <InputError message={errors.current_activity} />
            </div>

            <div class="grid gap-2">
                <Label for="openrouter_model">OpenRouter model</Label>
                <Input
                    id="openrouter_model"
                    bind:value={openrouterModel}
                    placeholder="anthropic/claude-sonnet-4.5"
                />
                <div class="flex flex-wrap gap-2">
                    {#each modelPresets as preset (preset)}
                        <button
                            type="button"
                            class="cursor-pointer"
                            onclick={() => (openrouterModel = preset)}
                        >
                            <Badge
                                variant={openrouterModel === preset
                                    ? 'default'
                                    : 'outline'}>{preset}</Badge
                            >
                        </button>
                    {/each}
                </div>
                <InputError message={errors.openrouter_model} />
            </div>

            <div class="grid gap-2">
                <Label for="openrouter_api_key">OpenRouter API key</Label>
                <PasswordInput
                    id="openrouter_api_key"
                    name="openrouter_api_key"
                    placeholder={settings.hasApiKey
                        ? 'Leave blank to keep the current key'
                        : 'sk-or-...'}
                    autocomplete="off"
                />
                {#if settings.hasApiKey}
                    <Badge variant="outline" class="w-fit"
                        >API key configured</Badge
                    >
                {:else}
                    <Badge variant="destructive" class="w-fit"
                        >No API key set</Badge
                    >
                {/if}
                <InputError message={errors.openrouter_api_key} />
            </div>

            <Button type="submit" disabled={processing}>
                {#if processing}<Spinner />{/if}
                Save settings
            </Button>
        {/snippet}
    </Form>
</div>
