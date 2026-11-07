<script setup lang="ts">
import ExternalLink from '@/components/public/ExternalLink.vue';
import { Link } from '@inertiajs/vue3';
import type { GuidePage } from './types';
defineProps<{ pages: GuidePage[]; currentTopic?: string }>();
defineEmits<{ navigate: [] }>();
</script>

<template>
    <nav :aria-label="$t('public.docs.navigation')">
        <p
            class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
        >
            {{ $t('public.docs.guide') }}
        </p>
        <ul class="mt-4 border-l">
            <li v-for="page in pages" :key="page.topic">
                <Link
                    :href="page.url"
                    :aria-current="
                        page.topic === currentTopic ? 'page' : undefined
                    "
                    class="-ml-px flex min-h-11 items-center border-l-2 border-transparent px-4 py-2 text-sm text-muted-foreground hover:text-foreground aria-[current=page]:border-primary aria-[current=page]:text-[var(--primary-text)]"
                    @click="$emit('navigate')"
                    >{{ page.title }}</Link
                >
            </li>
        </ul>
        <ExternalLink
            href="https://ignite-docs.promethys.dev"
            class="mt-6 inline-flex min-h-11 items-center text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
            >{{ $t('public.nav.developer_docs') }}</ExternalLink
        >
    </nav>
</template>
