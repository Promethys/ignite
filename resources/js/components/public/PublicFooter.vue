<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ExternalLink from '@/components/public/ExternalLink.vue';
import { getCopyrightYears } from '@/lib/utils';
import { features, home, privacy, selfHost, terms } from '@/routes';
import { index as docsIndex } from '@/routes/docs';
import type { AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const columns = computed(() => [
    {
        key: 'product',
        links: [
            { key: 'features', href: features().url, internal: true },
            { key: 'docs', href: docsIndex().url, internal: true },
        ],
    },
    {
        key: 'project',
        links: [
            { key: 'self_host', href: selfHost().url, internal: true },
            {
                key: 'developer_docs',
                href: 'https://ignite-docs.promethys.dev/',
            },
            { key: 'github', href: page.props.githubUrl },
            { key: 'releases', href: `${page.props.githubUrl}/releases` },
            { key: 'report_bug', href: `${page.props.githubUrl}/issues` },
        ],
    },
    {
        key: 'legal',
        links: [
            { key: 'privacy', href: privacy().url, internal: true },
            { key: 'terms', href: terms().url, internal: true },
            { key: 'contact', href: `mailto:${page.props.supportEmail}` },
        ],
    },
]);
</script>

<template>
    <footer class="relative border-t">
        <div
            class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr_1fr]"
        >
            <div class="max-w-xs space-y-3 sm:col-span-3 lg:col-span-1">
                <Link
                    :href="home()"
                    :aria-label="$t('public.nav.home')"
                    class="inline-flex"
                    ><AppLogo
                /></Link>
                <p class="text-sm text-muted-foreground">
                    {{ $t('public.footer.description') }}
                </p>
            </div>
            <nav
                v-for="column in columns"
                :key="column.key"
                :aria-label="$t(`public.footer.${column.key}`)"
            >
                <h2
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    {{ $t(`public.footer.${column.key}`) }}
                </h2>
                <ul class="mt-3">
                    <li v-for="link in column.links" :key="link.key">
                        <component
                            :is="
                                link.internal
                                    ? Link
                                    : link.href.startsWith('mailto:')
                                      ? 'a'
                                      : ExternalLink
                            "
                            :href="link.href"
                            class="inline-flex min-h-11 items-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                            >{{ $t(`public.nav.${link.key}`) }}</component
                        >
                    </li>
                </ul>
            </nav>
        </div>
        <div class="border-t">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-muted-foreground sm:flex-row sm:justify-between sm:px-6"
            >
                <span>© {{ getCopyrightYears() }} Ignite</span>
                <span>{{ $t('public.footer.license') }}</span>
            </div>
        </div>
    </footer>
</template>
