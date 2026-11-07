<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Toaster } from '../ui/sonner';
import PublicFooter from './PublicFooter.vue';
import PublicHeader from './PublicHeader.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head v-if="title" :title="title">
        <meta
            v-if="description"
            head-key="description"
            name="description"
            :content="description"
        />
    </Head>

    <div
        class="public-surface dark relative flex min-h-screen flex-col overflow-x-clip bg-background text-foreground"
    >
        <div class="public-surface-grid pointer-events-none absolute inset-0" />

        <a
            href="#main"
            class="sr-only z-50 border bg-background px-4 py-3 text-sm font-semibold focus:not-sr-only focus:absolute focus:top-4 focus:left-4"
            >{{ $t('public.skip_to_content') }}</a
        >

        <PublicHeader class="relative" />

        <slot />

        <PublicFooter />
    </div>

    <Toaster
        position="top-right"
        close-button
        close-button-position="top-right"
        theme="dark"
    />
</template>
