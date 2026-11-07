<script setup lang="ts">
import ClosingCta from '@/components/public/ClosingCta.vue';
import CodeBlock from '@/components/public/CodeBlock.vue';
import ExternalLink from '@/components/public/ExternalLink.vue';
import PageIntro from '@/components/public/PageIntro.vue';
import PublicShell from '@/components/public/PublicShell.vue';
import { Button } from '@/components/ui/button';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { BookOpen, Bug, Lightbulb, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const licencePoints = ['read', 'competing', 'mit'];
const contributions = computed(() => [
    { key: 'bug', icon: Bug, href: `${page.props.githubUrl}/issues` },
    { key: 'feature', icon: Lightbulb, href: `${page.props.githubUrl}/issues` },
    {
        key: 'contributing',
        icon: BookOpen,
        href: `${page.props.githubUrl}/blob/main/CONTRIBUTING.md`,
    },
]);
</script>

<template>
    <PublicShell
        :title="$t('public.self_host.title')"
        :description="$t('public.self_host.lead')"
    >
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto w-full max-w-7xl flex-1 px-4 pt-14 pb-16 sm:px-6 md:pt-20"
        >
            <PageIntro
                :label="$t('public.nav.self_host')"
                :title="$t('public.self_host.title')"
                :lead="$t('public.self_host.lead')"
            />
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <Button size="lg" as-child>
                    <ExternalLink :href="page.props.githubUrl">{{
                        $t('public.self_host.github')
                    }}</ExternalLink>
                </Button>
                <Button size="lg" variant="outline" as-child>
                    <ExternalLink href="https://ignite-docs.promethys.dev/">{{
                        $t('public.self_host.developer_docs')
                    }}</ExternalLink>
                </Button>
            </div>

            <section
                aria-labelledby="licence"
                class="mt-20 grid gap-8 border-t pt-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        {{ $t('public.self_host.licence_label') }}
                    </p>
                    <h2
                        id="licence"
                        class="mt-3 font-display text-3xl font-semibold tracking-tight"
                    >
                        {{ $t('public.self_host.licence_title') }}
                    </h2>
                    <p
                        class="mt-4 font-mono text-sm text-[var(--primary-text)]"
                    >
                        {{ $t('public.self_host.licence_name') }}
                    </p>
                </div>
                <div>
                    <ul class="divide-y border bg-card">
                        <li
                            v-for="point in licencePoints"
                            :key="point"
                            class="p-5"
                        >
                            <h3 class="font-display text-lg font-semibold">
                                {{
                                    $t(
                                        `public.self_host.licence_${point}_title`,
                                    )
                                }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ $t(`public.self_host.licence_${point}`) }}
                            </p>
                        </li>
                    </ul>
                    <ExternalLink
                        :href="`${page.props.githubUrl}/blob/main/LICENSE`"
                        class="mt-4 min-h-11 text-sm text-[var(--primary-text)] underline underline-offset-4"
                        >{{ $t('public.self_host.licence_link') }}</ExternalLink
                    >
                </div>
            </section>

            <section aria-labelledby="stack" class="mt-16 border-t pt-12">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    {{ $t('public.self_host.stack_label') }}
                </p>
                <h2
                    id="stack"
                    class="mt-3 max-w-3xl font-display text-3xl font-semibold tracking-tight"
                >
                    {{ $t('public.self_host.stack_title') }}
                </h2>
            </section>

            <section aria-labelledby="run" class="mt-16 border-t pt-12">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    {{ $t('public.self_host.run_label') }}
                </p>
                <h2
                    id="run"
                    class="mt-3 font-display text-3xl font-semibold tracking-tight"
                >
                    {{ $t('public.self_host.run_title') }}
                </h2>
                <p class="mt-4 text-muted-foreground">
                    {{ $t('public.self_host.requirements') }}
                </p>
                <ol class="mt-8 grid gap-8">
                    <li v-for="step in 6" :key="step" class="min-w-0">
                        <h3 class="flex items-start gap-3 font-medium">
                            <span
                                class="grid size-7 shrink-0 place-items-center bg-primary text-sm text-primary-foreground"
                                aria-hidden="true"
                                >{{ step }}</span
                            >
                            {{ $t(`public.self_host.steps.${step}.title`) }}
                        </h3>
                        <p
                            class="mt-3 text-sm leading-relaxed text-muted-foreground"
                        >
                            {{
                                $t(`public.self_host.steps.${step}.description`)
                            }}
                        </p>
                        <CodeBlock
                            class="mt-3"
                            :code="$t(`public.self_host.steps.${step}.code`)"
                        />
                        <div v-if="step === 4" class="docs-prose mt-4">
                            <aside
                                class="docs-callout docs-callout-warning"
                                aria-labelledby="master-key-warning"
                            >
                                <p
                                    id="master-key-warning"
                                    class="docs-callout-title"
                                >
                                    <TriangleAlert
                                        class="docs-callout-icon"
                                        aria-hidden="true"
                                    />
                                    {{ $t('public.self_host.warning_title') }}
                                </p>
                                <p>{{ $t('public.self_host.warning') }}</p>
                            </aside>
                        </div>
                    </li>
                </ol>
                <ExternalLink
                    href="https://ignite-docs.promethys.dev/self-hosting"
                    class="mt-6 min-h-11 text-[var(--primary-text)] underline underline-offset-4"
                    >{{ $t('public.self_host.guide') }}</ExternalLink
                >
            </section>

            <section aria-labelledby="contribute" class="mt-16 border-t pt-12">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    {{ $t('public.self_host.contribute_label') }}
                </p>
                <h2
                    id="contribute"
                    class="mt-3 font-display text-3xl font-semibold tracking-tight"
                >
                    {{ $t('public.self_host.contribute_title') }}
                </h2>
                <ul class="mt-8 grid gap-px border bg-border md:grid-cols-3">
                    <li
                        v-for="item in contributions"
                        :key="item.key"
                        class="min-w-0 bg-background"
                    >
                        <ExternalLink
                            :href="item.href"
                            class="min-h-24 w-full gap-3 p-6 font-medium hover:bg-card"
                        >
                            <component
                                :is="item.icon"
                                class="size-5 shrink-0 text-[var(--primary-text)]"
                                aria-hidden="true"
                            />
                            {{ $t(`public.self_host.${item.key}`) }}
                        </ExternalLink>
                    </li>
                </ul>
            </section>
            <ClosingCta />
        </main>
    </PublicShell>
</template>
