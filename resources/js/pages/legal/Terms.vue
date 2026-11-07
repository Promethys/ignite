<script setup lang="ts">
import PageIntro from '@/components/public/PageIntro.vue';
import PublicShell from '@/components/public/PublicShell.vue';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';

const page = usePage<AppPageProps>();
</script>

<template>
    <PublicShell
        :title="$t('public.terms.title')"
        :description="$t('public.terms.lead')"
    >
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-14 sm:px-6 md:py-20"
        >
            <PageIntro
                :label="$t('public.nav.terms')"
                :title="$t('public.terms.title')"
                :lead="$t('public.terms.lead')"
            />
            <p class="mt-8 text-muted-foreground">
                {{ $t('public.terms.onward') }}
            </p>
            <a
                :href="`mailto:${page.props.supportEmail}`"
                class="mt-3 inline-flex min-h-11 items-center text-[var(--primary-text)] underline underline-offset-4"
                >{{ $t('public.terms.link') }}</a
            >
        </main>
    </PublicShell>
</template>
