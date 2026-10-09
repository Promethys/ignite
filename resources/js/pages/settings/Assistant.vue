<script setup lang="ts">
import ProviderIcon from '@/components/assistant/ProviderIcon.vue';
import CharacterCounter from '@/components/CharacterCounter.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import InputRequiredIndicator from '@/components/InputRequiredIndicator.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { FIELD_LIMITS } from '@/lib/field-limits';
import { destroy, index, makeDefault, store, update } from '@/routes/assistant';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface AssistantProviderOption {
    value: string;
    label: string;
}

interface SavedAssistantKey {
    id: number;
    provider: string;
    key_suffix: string;
    model: string | null;
    is_default: boolean;
}

const props = defineProps<{
    providers: AssistantProviderOption[];
    assistantKeys: SavedAssistantKey[];
    instanceConfigured: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'settings.assistant.breadcrumb',
        href: index.url(),
    },
];

function savedKeyFor(provider: string): SavedAssistantKey | undefined {
    return props.assistantKeys.find((key) => key.provider === provider);
}

const dialogProvider = ref<AssistantProviderOption | null>(null);
const keyBeingEdited = computed(() =>
    dialogProvider.value ? savedKeyFor(dialogProvider.value.value) : undefined,
);

const form = useForm({
    provider: '',
    api_key: '',
    model: '',
    consent: false,
});

function openDialog(provider: AssistantProviderOption): void {
    form.reset();
    form.clearErrors();
    form.provider = provider.value;
    form.model = savedKeyFor(provider.value)?.model ?? '';
    dialogProvider.value = provider;
}

function closeDialog(): void {
    dialogProvider.value = null;
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: closeDialog };

    if (keyBeingEdited.value) {
        form.submit(update(keyBeingEdited.value.id), options);
    } else {
        form.submit(store(), options);
    }
}

function useKey(key: SavedAssistantKey): void {
    router.patch(makeDefault(key.id).url, {}, { preserveScroll: true });
}

const keyBeingRemoved = ref<SavedAssistantKey | null>(null);
const providerBeingRemoved = computed(
    () =>
        props.providers.find(
            (provider) => provider.value === keyBeingRemoved.value?.provider,
        )?.label ?? '',
);

function remove(): void {
    if (!keyBeingRemoved.value) {
        return;
    }

    router.delete(destroy(keyBeingRemoved.value.id).url, {
        preserveScroll: true,
        onFinish: () => (keyBeingRemoved.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.assistant.head')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.assistant.title')"
                    :description="$t('settings.assistant.description')"
                />

                <p
                    v-if="instanceConfigured"
                    class="text-sm text-muted-foreground"
                    data-test="assistant-instance-configured"
                >
                    {{ $t('settings.assistant.instance_configured') }}
                </p>

                <div class="@container space-y-4">
                    <Item
                        v-for="provider in providers"
                        :key="provider.value"
                        variant="outline"
                        :data-test="`assistant-provider-${provider.value}`"
                        class="@lg:flex-nowrap"
                    >
                        <ItemMedia>
                            <ProviderIcon
                                :provider="provider.value"
                                :label="provider.label"
                            />
                        </ItemMedia>
                        <ItemContent class="min-w-0">
                            <ItemTitle>
                                {{ provider.label }}
                                <Badge
                                    v-if="
                                        savedKeyFor(provider.value)?.is_default
                                    "
                                >
                                    {{ $t('settings.assistant.default_badge') }}
                                </Badge>
                            </ItemTitle>
                            <ItemDescription>
                                <template v-if="savedKeyFor(provider.value)">
                                    <p>
                                        {{
                                            $t(
                                                'settings.assistant.key_ending',
                                                {
                                                    suffix: savedKeyFor(
                                                        provider.value,
                                                    )!.key_suffix,
                                                },
                                            )
                                        }}
                                    </p>
                                    <p>
                                        {{
                                            savedKeyFor(provider.value)!.model
                                                ? $t(
                                                      'settings.assistant.model',
                                                      {
                                                          model: savedKeyFor(
                                                              provider.value,
                                                          )!.model as string,
                                                      },
                                                  )
                                                : $t(
                                                      'settings.assistant.default_model',
                                                  )
                                        }}
                                    </p>
                                </template>
                                <template v-else>
                                    {{ $t('settings.assistant.not_connected') }}
                                </template>
                            </ItemDescription>
                        </ItemContent>
                        <ItemActions
                            class="basis-full flex-wrap pl-12 @lg:basis-auto @lg:flex-nowrap @lg:pl-0"
                        >
                            <template v-if="savedKeyFor(provider.value)">
                                <Button
                                    v-if="
                                        !savedKeyFor(provider.value)!.is_default
                                    "
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        useKey(savedKeyFor(provider.value)!)
                                    "
                                >
                                    {{ $t('settings.assistant.use') }}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openDialog(provider)"
                                >
                                    {{ $t('settings.assistant.edit') }}
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="
                                        keyBeingRemoved = savedKeyFor(
                                            provider.value,
                                        )!
                                    "
                                >
                                    {{ $t('settings.assistant.remove') }}
                                </Button>
                            </template>
                            <Button
                                v-else
                                size="sm"
                                @click="openDialog(provider)"
                            >
                                {{ $t('settings.assistant.connect') }}
                            </Button>
                        </ItemActions>
                    </Item>
                </div>
            </div>

            <Dialog
                :open="dialogProvider !== null"
                @update:open="(open) => !open && closeDialog()"
            >
                <DialogContent v-if="dialogProvider" class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                $t(
                                    keyBeingEdited
                                        ? 'settings.assistant.edit_title'
                                        : 'settings.assistant.connect_title',
                                    { provider: dialogProvider.label },
                                )
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                $t('settings.assistant.dialog_description', {
                                    provider: dialogProvider.label,
                                })
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        id="assistant-key-form"
                        class="space-y-4"
                        @submit.prevent="submit"
                    >
                        <div class="grid gap-2">
                            <Label for="api_key">
                                <span>
                                    {{ $t('settings.assistant.api_key') }}
                                    <InputRequiredIndicator
                                        v-if="!keyBeingEdited"
                                    />
                                </span>
                            </Label>
                            <PasswordInput
                                id="api_key"
                                v-model="form.api_key"
                                autocomplete="off"
                                :aria-required="!keyBeingEdited"
                                :placeholder="
                                    keyBeingEdited
                                        ? $t(
                                              'settings.assistant.api_key_keep',
                                              {
                                                  suffix: keyBeingEdited.key_suffix,
                                              },
                                          )
                                        : $t(
                                              'settings.assistant.api_key_placeholder',
                                          )
                                "
                            />
                            <InputError :message="form.errors.api_key" />
                            <InputError :message="form.errors.provider" />
                        </div>

                        <div class="grid gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <Label for="model">{{
                                    $t('settings.assistant.model_label')
                                }}</Label>
                                <CharacterCounter
                                    :value="form.model"
                                    :max="FIELD_LIMITS.assistantModel"
                                />
                            </div>
                            <Input
                                id="model"
                                v-model="form.model"
                                type="text"
                                autocomplete="off"
                                :placeholder="
                                    $t('settings.assistant.model_placeholder')
                                "
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ $t('settings.assistant.model_help') }}
                            </p>
                            <InputError :message="form.errors.model" />
                        </div>

                        <div v-if="!keyBeingEdited" class="grid gap-2">
                            <div class="flex items-start gap-2">
                                <Checkbox
                                    id="consent"
                                    :model-value="form.consent"
                                    @update:model-value="
                                        (checked) =>
                                            (form.consent = checked === true)
                                    "
                                />
                                <Label
                                    for="consent"
                                    class="text-sm leading-tight font-normal"
                                >
                                    {{
                                        $t('settings.assistant.consent', {
                                            provider: dialogProvider.label,
                                        })
                                    }}
                                </Label>
                            </div>
                            <InputError :message="form.errors.consent" />
                        </div>
                    </form>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                {{ $t('common.actions.cancel') }}
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            form="assistant-key-form"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? $t('settings.assistant.saving')
                                    : $t('settings.assistant.save')
                            }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                :open="keyBeingRemoved !== null"
                @update:open="(open) => !open && (keyBeingRemoved = null)"
            >
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>
                            {{ $t('settings.assistant.remove_title') }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                $t('settings.assistant.remove_confirm', {
                                    provider: providerBeingRemoved,
                                })
                            }}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                {{ $t('common.actions.cancel') }}
                            </Button>
                        </DialogClose>
                        <Button variant="destructive" @click="remove">
                            {{ $t('settings.assistant.remove') }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </SettingsLayout>
    </AppLayout>
</template>
