<script setup lang="ts">
import PageIntro from '@/components/public/PageIntro.vue';
import DocsArticle from '@/components/public/docs/DocsArticle.vue';
import DocsLayout from '@/components/public/docs/DocsLayout.vue';
import DocsSearch from '@/components/public/docs/DocsSearch.vue';
import type { DocsPageProps } from '@/components/public/docs/types';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';
defineProps<DocsPageProps>();
</script>

<template>
    <DocsLayout
        :pages="pages"
        :current-topic="article?.topic"
        :headings="article?.headings ?? []"
        :title="$t('public.docs.title')"
        :description="$t('public.docs.lead')"
    >
        <PageIntro
            :label="$t('public.nav.docs')"
            :title="$t('public.docs.title')"
            :lead="$t('public.docs.lead')"
        />
        <DocsSearch :search-index="searchIndex" class="mt-8" />
        <ul class="my-10 grid gap-px border bg-border sm:grid-cols-2">
            <li
                v-for="page in pages.filter(
                    (page) => page.topic !== 'getting-started',
                )"
                :key="page.topic"
                class="bg-background"
            >
                <Link
                    :href="page.url"
                    class="group flex h-full flex-col p-5 hover:bg-card"
                    ><span
                        class="flex items-start justify-between gap-3 font-display text-lg font-semibold group-hover:text-[var(--primary-text)]"
                        >{{ page.title
                        }}<ArrowUpRight
                            class="mt-1 size-4 shrink-0"
                            aria-hidden="true" /></span
                    ><span
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >{{ page.summary }}</span
                    ></Link
                >
            </li>
        </ul>
        <template v-if="article">
            <h2 class="mb-3 font-display text-3xl font-semibold">
                {{ article.title }}
            </h2>
            <p class="mb-8 leading-relaxed text-muted-foreground">
                {{ article.summary }}
            </p>
            <DocsArticle :article="article" :previous="previous" :next="next" />
        </template>
    </DocsLayout>
</template>
