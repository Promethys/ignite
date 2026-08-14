<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import { Button } from '@/components/ui/button';
import Github from '@/components/ui/icon/Github.vue';
import Google from '@/components/ui/icon/Google.vue';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { providerLabel } from '@/lib/sso';
import { formatDate } from '@/lib/utils';
import { index } from '@/routes/connected-accounts';
import { edit as editPassword } from '@/routes/password';
import { logout, redirect } from '@/routes/sso';

interface ConnectedSocialAccount {
    id: number;
    provider: string;
    provider_email: string | null;
    created_at: string;
}

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'settings.connected_accounts.breadcrumb',
        href: index().url,
    },
];

const props = defineProps<{
    connectedProviders: ConnectedSocialAccount[];
}>();

const page = usePage();
const providers = page.props.ssoProviders;

function connectedProviderData(
    provider: string,
): ConnectedSocialAccount | undefined {
    return props.connectedProviders.find((p) => p.provider === provider);
}

function isConnected(provider: string): boolean {
    return connectedProviderData(provider) !== undefined;
}

function isLastCredential(provider: string): boolean {
    return (
        isConnected(provider) &&
        props.connectedProviders.length === 1 &&
        !page.props.auth.user.has_password
    );
}

function disconnect(provider: string) {
    router.delete(logout({ provider }).url, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.connected_accounts.head')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.connected_accounts.title')"
                    :description="$t('settings.connected_accounts.description')"
                />

                <div class="space-y-4">
                    <Item
                        v-for="provider in providers"
                        :key="provider"
                        variant="outline"
                    >
                        <ItemMedia>
                            <Google
                                v-if="provider === 'google'"
                                class="size-5"
                            />
                            <Github
                                v-else-if="provider === 'github'"
                                class="size-5"
                            />
                        </ItemMedia>
                        <ItemContent>
                            <ItemTitle>{{ providerLabel(provider) }}</ItemTitle>
                            <ItemDescription>
                                <template v-if="isConnected(provider)">
                                    <p
                                        v-if="
                                            connectedProviderData(provider)
                                                ?.provider_email
                                        "
                                    >
                                        {{
                                            connectedProviderData(provider)
                                                ?.provider_email
                                        }}
                                    </p>
                                    <p>
                                        {{
                                            $t(
                                                'settings.connected_accounts.linked_since',
                                                {
                                                    date: formatDate(
                                                        connectedProviderData(
                                                            provider,
                                                        )!.created_at,
                                                    ),
                                                },
                                            )
                                        }}
                                    </p>
                                    <p
                                        v-if="isLastCredential(provider)"
                                        class="mt-1 text-muted-foreground"
                                    >
                                        {{
                                            $t(
                                                'settings.connected_accounts.last_credential',
                                            )
                                        }}
                                    </p>
                                </template>
                                <template v-else>
                                    {{
                                        $t(
                                            'settings.connected_accounts.not_linked',
                                        )
                                    }}
                                </template>
                            </ItemDescription>
                        </ItemContent>
                        <ItemActions>
                            <Button
                                v-if="isLastCredential(provider)"
                                variant="outline"
                                size="sm"
                                as="a"
                                :href="editPassword().url"
                            >
                                {{
                                    $t(
                                        'settings.connected_accounts.create_password',
                                    )
                                }}
                            </Button>
                            <Button
                                v-else-if="isConnected(provider)"
                                variant="destructive"
                                size="sm"
                                @click="disconnect(provider)"
                            >
                                {{
                                    $t('settings.connected_accounts.disconnect')
                                }}
                            </Button>
                            <Button
                                v-else
                                size="sm"
                                as="a"
                                :href="redirect({ provider }).url"
                            >
                                {{ $t('settings.connected_accounts.connect') }}
                            </Button>
                        </ItemActions>
                    </Item>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
