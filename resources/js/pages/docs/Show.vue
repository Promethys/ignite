<script setup lang="ts">
import PageIntro from '@/components/public/PageIntro.vue';
import DocsArticle from '@/components/public/docs/DocsArticle.vue';
import DocsLayout from '@/components/public/docs/DocsLayout.vue';
import DocsSearch from '@/components/public/docs/DocsSearch.vue';
import type { DocsPageProps } from '@/components/public/docs/types';
import { index } from '@/routes/docs';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
defineProps<DocsPageProps>();
</script>

<template>
    <DocsLayout
        :pages="pages"
        :current-topic="article?.topic"
        :headings="article?.headings ?? []"
        :title="article?.title ?? $t('public.docs.not_found_title')"
        :description="article?.summary ?? $t('public.docs.not_found_lead')"
    >
        <template v-if="article">
            <nav
                :aria-label="$t('public.docs.breadcrumb')"
                class="mb-6 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Link :href="index()" class="hover:text-foreground">{{
                    $t('public.nav.docs')
                }}</Link
                ><ChevronRight
                    class="size-3 shrink-0"
                    aria-hidden="true"
                /><span aria-current="page">{{ article.title }}</span>
            </nav>
            <h1
                class="font-display text-4xl leading-tight font-bold text-balance sm:text-5xl"
            >
                {{ article.title }}
            </h1>
            <p class="mt-4 text-lg leading-relaxed text-muted-foreground">
                {{ article.summary }}
            </p>
            <DocsSearch :search-index="searchIndex" class="my-8" />
            <DocsArticle :article="article" :previous="previous" :next="next" />
        </template>
        <template v-else>
            <PageIntro
                label="404"
                :title="$t('public.docs.not_found_title')"
                :lead="$t('public.docs.not_found_lead')"
            />
            <Link
                :href="index()"
                class="mt-6 inline-flex min-h-11 items-center text-sm text-[var(--primary-text)] underline underline-offset-4"
                >{{ $t('public.docs.back_home') }}</Link
            >
            <DocsSearch :search-index="searchIndex" class="mt-8" />
        </template>
    </DocsLayout>
</template>
