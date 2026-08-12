<script setup lang="ts">
import { getCopyrightYears } from '@/lib/utils';
import { AppPageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { MoveUpRight } from 'lucide-vue-next';
import { Toaster } from '../ui/sonner';
import PublicHeader from './PublicHeader.vue';

defineProps<{
    title?: string;
}>();

const supportEmail = usePage().props.supportEmail;
const appPublicRepo = usePage<AppPageProps>().props.githubUrl;
</script>

<template>
    <Head v-if="title" :title="title" />

    <div
        class="public-surface dark relative flex min-h-screen flex-col overflow-x-hidden bg-background text-foreground"
    >
        <div class="public-surface-grid pointer-events-none absolute inset-0" />

        <PublicHeader class="relative" />

        <slot />

        <footer class="relative border-t">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 pt-5 pb-10 text-xs text-muted-foreground sm:px-6"
            >
                <span>© {{ getCopyrightYears() }} Ignite</span>
                <span class="flex items-center gap-3">
                    <a
                        :href="`mailto:${supportEmail}`"
                        target="_blank"
                        rel="noopener"
                        class="underline-offset-2 hover:underline"
                    >
                        {{ $t('landing.footer.contact') }}
                    </a>
                    ·
                    <a
                        :href="appPublicRepo"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1 underline-offset-2 hover:underline"
                    >
                        {{ $t('common.nav.repository') }}
                        <MoveUpRight class="size-3" />
                    </a>
                </span>
            </div>
        </footer>
    </div>

    <Toaster
        position="top-right"
        close-button
        close-button-position="top-right"
        theme="dark"
    />
</template>
