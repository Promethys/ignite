<script setup lang="ts">
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { destroy, index, store } from '@/routes/api-tokens';
import { type BreadcrumbItem, type PersonalAccessToken } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Check, Copy } from 'lucide-vue-next';
import moment from 'moment';
import { ref, watch } from 'vue';

interface NewToken {
    name: string;
    token: string;
}

interface Props {
    tokens: PersonalAccessToken[];
    newToken?: NewToken | null;
}

const props = withDefaults(defineProps<Props>(), {
    newToken: null,
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'settings.api_tokens.breadcrumb',
        href: index.url(),
    },
];

const createForm = useForm({
    name: '',
    abilities: ['read'] as string[],
}).withPrecognition(store());

const abilityOptions = [
    { value: 'read', label: 'settings.api_tokens.ability_read' },
    { value: 'write', label: 'settings.api_tokens.ability_write' },
    { value: 'delete', label: 'settings.api_tokens.ability_delete' },
];

function toggleAbility(value: string, checked: boolean | string): void {
    const isChecked = checked === true;
    if (isChecked) {
        if (!createForm.abilities.includes(value)) {
            createForm.abilities.push(value);
        }
    } else {
        createForm.abilities = createForm.abilities.filter(
            (ability) => ability !== value,
        );
    }
}

function submit(): void {
    createForm.submit(store(), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function revoke(tokenId: number): void {
    router.delete(destroy(tokenId), {
        preserveScroll: true,
    });
}

const copied = ref(false);
let copyResetTimeout: ReturnType<typeof setTimeout> | null = null;

function copyToken(): void {
    if (!props.newToken) {
        return;
    }

    navigator.clipboard?.writeText(props.newToken.token);
    copied.value = true;

    if (copyResetTimeout) {
        clearTimeout(copyResetTimeout);
    }

    copyResetTimeout = setTimeout(() => {
        copied.value = false;
    }, 2000);
}

// Reset the copied state whenever a new token is minted so the check icon
// does not bleed across reveals.
watch(
    () => props.newToken,
    () => {
        copied.value = false;
        if (copyResetTimeout) {
            clearTimeout(copyResetTimeout);
            copyResetTimeout = null;
        }
    },
);

function formatDate(value: string | null): string {
    if (!value) {
        return trans('settings.api_tokens.never');
    }

    return moment(value).format('L LT');
}

function abilitiesList(token: PersonalAccessToken): string[] {
    return token.abilities ?? [];
}

function abilityShortLabel(ability: string): string {
    return trans(`settings.api_tokens.ability_short.${ability}`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.api_tokens.head')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.api_tokens.title')"
                    :description="$t('settings.api_tokens.description')"
                />

                <!-- One-time reveal of the freshly minted token -->
                <div
                    v-if="newToken"
                    class="rounded-lg border border-primary/40 bg-primary/5 p-4"
                >
                    <p class="font-medium">
                        {{ $t('settings.api_tokens.reveal_title') }}
                    </p>
                    <p class="mb-3 text-sm text-muted-foreground">
                        {{ $t('settings.api_tokens.reveal_description') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <Input
                            :model-value="newToken.token"
                            readonly
                            class="font-mono text-xs"
                            @focus="
                                ($event.target as HTMLInputElement).select()
                            "
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            size="icon"
                            :title="$t('settings.api_tokens.copy')"
                            @click="copyToken"
                        >
                            <Check v-if="copied" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Create form -->
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">
                            <span>
                                {{ $t('settings.api_tokens.name') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <Input
                            id="name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            aria-required="true"
                            :placeholder="
                                $t('settings.api_tokens.name_placeholder')
                            "
                            @change="createForm.validate('name')"
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>{{ $t('settings.api_tokens.abilities') }}</Label>
                        <div class="space-y-3">
                            <div
                                v-for="option in abilityOptions"
                                :key="option.value"
                                class="flex items-start gap-2"
                            >
                                <Checkbox
                                    :id="`ability-${option.value}`"
                                    :model-value="
                                        createForm.abilities.includes(
                                            option.value,
                                        )
                                    "
                                    @update:model-value="
                                        (checked) =>
                                            toggleAbility(option.value, checked)
                                    "
                                />
                                <Label
                                    :for="`ability-${option.value}`"
                                    class="text-sm leading-tight font-normal"
                                >
                                    {{ $t(option.label) }}
                                </Label>
                            </div>
                        </div>
                        <InputError :message="createForm.errors.abilities" />
                    </div>

                    <Button type="submit" :disabled="createForm.processing">
                        {{
                            createForm.processing
                                ? $t('settings.api_tokens.creating')
                                : $t('settings.api_tokens.create')
                        }}
                    </Button>
                </form>

                <Separator />

                <!-- Token list -->
                <div class="space-y-4">
                    <h4 class="font-medium">
                        {{ $t('settings.api_tokens.your_tokens') }}
                    </h4>

                    <p
                        v-if="tokens.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('settings.api_tokens.empty') }}
                    </p>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="token in tokens"
                            :key="token.id"
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        token.name
                                    }}</span>
                                    <Badge
                                        v-for="ability in abilitiesList(token)"
                                        :key="ability"
                                        class="text-2xs font-medium"
                                    >
                                        {{ abilityShortLabel(ability) }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ $t('settings.api_tokens.created_at') }}:
                                    {{ formatDate(token.created_at) }}
                                    &middot;
                                    {{ $t('settings.api_tokens.last_used') }}:
                                    {{ formatDate(token.last_used_at) }}
                                </p>
                            </div>

                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="destructive" size="sm">
                                        {{ $t('settings.api_tokens.revoke') }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px]">
                                    <DialogHeader>
                                        <DialogTitle>
                                            {{
                                                $t(
                                                    'settings.api_tokens.revoke_title',
                                                )
                                            }}
                                        </DialogTitle>
                                        <DialogDescription>
                                            {{
                                                $t(
                                                    'settings.api_tokens.revoke_confirm',
                                                )
                                            }}
                                        </DialogDescription>
                                    </DialogHeader>

                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button
                                                type="button"
                                                variant="secondary"
                                            >
                                                {{
                                                    $t('common.actions.cancel')
                                                }}
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            variant="destructive"
                                            @click="revoke(token.id)"
                                        >
                                            {{
                                                $t('settings.api_tokens.revoke')
                                            }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
