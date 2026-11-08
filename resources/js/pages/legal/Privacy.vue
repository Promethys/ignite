<script setup lang="ts">
import PageIntro from '@/components/public/PageIntro.vue';
import PublicShell from '@/components/public/PublicShell.vue';
</script>

<template>
    <PublicShell
        :title="$t('public.privacy.title')"
        :description="$t('public.privacy.lead')"
    >
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-14 sm:px-6 md:py-20"
        >
            <PageIntro
                :label="$t('public.nav.privacy')"
                :title="$t('public.privacy.title')"
                :lead="$t('public.privacy.lead')"
            />
            <p class="mt-8 text-muted-foreground">
                {{ $t('public.privacy.onward') }}
            </p>
            <a
                href="https://ignite-docs.promethys.dev/features/encryption"
                class="mt-3 inline-flex min-h-11 items-center text-[var(--primary-text)] underline underline-offset-4"
                >{{ $t('public.privacy.link') }}</a
            >
        </main>
    </PublicShell>
</template>
