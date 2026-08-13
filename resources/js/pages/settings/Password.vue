<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputRequiredIndicator from '@/components/InputRequiredIndicator.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { type BreadcrumbItem } from '@/types';
import { type SsoProvider } from '@/types/models';
import { Info } from 'lucide-vue-next';

const props = defineProps<{
    hasPassword: boolean;
    socialAccounts: SsoProvider[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'settings.password.breadcrumb',
        href: edit().url,
    },
];

const providerLabels: Record<SsoProvider, string> = {
    google: 'Google',
    github: 'GitHub',
};

const providers = computed(() =>
    props.socialAccounts
        .map((provider) => providerLabels[provider] ?? provider)
        .join(', '),
);

const isLinked = computed(() => props.socialAccounts.length > 0);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.password.head')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="
                        hasPassword
                            ? $t('settings.password.title')
                            : $t('settings.password.create_title')
                    "
                    :description="
                        hasPassword
                            ? $t('settings.password.description')
                            : $t('settings.password.create_description')
                    "
                />

                <Alert v-if="isLinked">
                    <Info aria-hidden="true" />
                    <AlertTitle>
                        {{
                            $t('settings.password.linked_title', {
                                providers,
                            })
                        }}
                    </AlertTitle>
                    <AlertDescription>
                        {{
                            hasPassword
                                ? $t('settings.password.linked_with_password', {
                                      providers,
                                  })
                                : $t(
                                      'settings.password.linked_without_password',
                                      { providers },
                                  )
                        }}
                    </AlertDescription>
                </Alert>

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div v-if="hasPassword" class="grid gap-2">
                        <Label for="current_password">
                            <span>
                                {{ $t('settings.password.current') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            required
                            :placeholder="
                                $t('settings.password.current_placeholder')
                            "
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">
                            <span>
                                {{ $t('settings.password.new') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            required
                            :placeholder="
                                $t('settings.password.new_placeholder')
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('settings.password.password_requirements') }}
                        </p>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            <span>
                                {{ $t('settings.password.confirm') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            required
                            :placeholder="
                                $t('settings.password.confirm_placeholder')
                            "
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                            >{{
                                hasPassword
                                    ? $t('settings.password.save')
                                    : $t('settings.password.create_save')
                            }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ $t('common.status.saved') }}
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
