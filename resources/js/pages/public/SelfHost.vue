<script setup lang="ts">
import PageIntro from '@/components/public/PageIntro.vue';
import PublicShell from '@/components/public/PublicShell.vue';
</script>

<template>
    <PublicShell
        :title="$t('public.self_host.title')"
        :description="$t('public.self_host.lead')"
    >
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-14 sm:px-6 md:py-20"
        >
            <PageIntro
                :label="$t('public.nav.self_host')"
                :title="$t('public.self_host.title')"
                :lead="$t('public.self_host.lead')"
            />
            <p class="mt-8 text-muted-foreground">
                {{ $t('public.self_host.onward') }}
            </p>
            <a
                href="https://ignite-docs.promethys.dev/self-hosting"
                class="mt-3 inline-flex min-h-11 items-center text-[var(--primary-text)] underline underline-offset-4"
                >{{ $t('public.self_host.link') }}</a
            >
        </main>
    </PublicShell>
</template>
