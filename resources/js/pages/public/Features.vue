<script setup lang="ts">
import AssistantMarkdown from '@/components/assistant/AssistantMarkdown.vue';
import AssistantToolActivity from '@/components/assistant/AssistantToolActivity.vue';
import EntryHeatmap from '@/components/charts/EntryHeatmap.vue';
import GoalBadges from '@/components/goals/GoalBadges.vue';
import MultiStepProgress from '@/components/goals/progress/MultiStepProgress.vue';
import QuantifiableProgress from '@/components/goals/progress/QuantifiableProgress.vue';
import RecurringProgress from '@/components/goals/progress/RecurringProgress.vue';
import ClosingCta from '@/components/public/ClosingCta.vue';
import FaqAccordion from '@/components/public/FaqAccordion.vue';
import FeatureSection from '@/components/public/FeatureSection.vue';
import FeatureShowcase from '@/components/public/FeatureShowcase.vue';
import HeroProgressTrack from '@/components/public/HeroProgressTrack.vue';
import PageIntro from '@/components/public/PageIntro.vue';
import PublicShell from '@/components/public/PublicShell.vue';
import SectionNav from '@/components/public/SectionNav.vue';
import TerminalIllustration from '@/components/public/TerminalIllustration.vue';
import {
    assistantMessages,
    heatmap,
    multiStepGoal,
    progressEntries,
    quantifiableGoal,
    recurringGoal,
    showcaseImageDimensions,
    simpleGoal,
} from '@/lib/public-showcase';
import { selfHost } from '@/routes';
import { index as docsIndex, show as docsShow } from '@/routes/docs';
import { trans } from 'laravel-vue-i18n';
import { Check, LockKeyhole } from 'lucide-vue-next';
import {
    computed,
    defineAsyncComponent,
    onMounted,
    onUnmounted,
    ref,
} from 'vue';

const ids = [
    'goal-types',
    'progress',
    'streaks',
    'milestones',
    'assistant',
    'mcp',
    'make-it-yours',
    'your-data',
];
const sections = computed(() => [
    ...ids.map((id) => ({ id, label: trans(`public.features.${id}.title`) })),
    { id: 'faq', label: trans('public.faq.label') },
]);
const docs: Record<string, string> = {
    'goal-types': docsShow('goal-types').url,
    progress: docsShow('tracking-progress').url,
    streaks: `${docsShow('goal-types').url}#recurring`,
    milestones: `${docsShow('tracking-progress').url}#multi-step-goals-complete-milestones`,
    assistant: docsShow('assistant').url,
    mcp: `${docsIndex().url}#connect-your-ai-assistant`,
    'make-it-yours': docsShow('themes').url,
    'your-data': 'https://ignite-docs.promethys.dev/features/encryption',
};
const points: Record<string, number> = {
    'goal-types': 4,
    assistant: 2,
    'your-data': 3,
};
const sectionPoints = (id: string) =>
    Array.from({ length: points[id] ?? 0 }, (_, index) =>
        trans(`public.features.${id}.points.${index}`, {
            current: String(quantifiableGoal.current_value),
            target: String(quantifiableGoal.target_value ?? 0),
            unit: quantifiableGoal.unit ?? '',
        }),
    );
const faq = computed(() =>
    [
        'free',
        'assistant-cost',
        'mcp-clients',
        'self-host',
        'delete',
        'open-source',
    ].map((id) => ({
        id,
        question: trans(`public.faq.items.${id}.question`),
        answer: trans(`public.faq.items.${id}.answer`),
        ...(id === 'self-host'
            ? { href: selfHost().url, linkLabel: trans('public.nav.self_host') }
            : {}),
    })),
);
const conversation = computed(() => assistantMessages(trans));
const goals = [simpleGoal, quantifiableGoal, recurringGoal, multiStepGoal];
const chartHost = ref<HTMLElement | null>(null);
const chartVisible = ref(false);
const ProgressChart = defineAsyncComponent(
    () => import('@/components/public/ProgressShowcase.vue'),
);
let observer: IntersectionObserver | undefined;
onMounted(() => {
    if (typeof IntersectionObserver === 'undefined') {
        chartVisible.value = true;
        return;
    }
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            chartVisible.value = true;
            observer?.disconnect();
        }
    });
    if (chartHost.value) observer.observe(chartHost.value);
});
onUnmounted(() => observer?.disconnect());
</script>

<template>
    <PublicShell
        :title="$t('public.nav.features')"
        :description="$t('public.intro.lead')"
    >
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto w-full max-w-7xl flex-1 px-4 pt-14 pb-16 sm:px-6 md:pt-20"
        >
            <PageIntro
                :label="$t('public.intro.label')"
                :title="$t('public.intro.title')"
                :lead="$t('public.intro.lead')"
            />
            <HeroProgressTrack class="mt-8" />
            <div
                class="mt-12 grid gap-10 lg:grid-cols-[200px_minmax(0,1fr)] lg:gap-12"
            >
                <SectionNav :items="sections" />
                <div class="min-w-0">
                    <FeatureSection
                        v-for="(id, index) in ids"
                        :id="id"
                        :key="id"
                        :index="index + 1"
                        :title="$t(`public.features.${id}.title`)"
                        :description="$t(`public.features.${id}.description`)"
                        :points="sectionPoints(id)"
                        :docs-href="docs[id]"
                        :reverse="index % 2 === 1"
                    >
                        <template #default>
                            <FeatureShowcase v-if="id === 'goal-types'">
                                <div
                                    class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                                >
                                    <div
                                        v-for="goal in goals"
                                        :key="goal.type"
                                        class="min-w-0 space-y-4 border p-4"
                                    >
                                        <p
                                            class="text-xs font-semibold tracking-wide text-[var(--primary-text)]"
                                        >
                                            {{ $t(`goals.types.${goal.type}`) }}
                                        </p>
                                        <h3
                                            class="font-display text-lg font-semibold"
                                        >
                                            {{
                                                $t(
                                                    `public.showcase.${goal.type}`,
                                                )
                                            }}
                                        </h3>
                                        <GoalBadges :goal="goal" />
                                        <p
                                            v-if="goal.type === 'simple'"
                                            class="flex items-center gap-2 text-sm text-success"
                                        >
                                            <Check
                                                class="size-4"
                                                aria-hidden="true"
                                            />{{
                                                $t('public.showcase.completed')
                                            }}
                                        </p>
                                        <QuantifiableProgress
                                            v-else-if="
                                                goal.type === 'quantifiable'
                                            "
                                            :item="goal"
                                        />
                                        <RecurringProgress
                                            v-else-if="
                                                goal.type === 'recurring'
                                            "
                                            :item="goal"
                                        />
                                        <MultiStepProgress
                                            v-else
                                            :item="goal"
                                        />
                                    </div>
                                </div>
                            </FeatureShowcase>
                            <FeatureShowcase v-else-if="id === 'progress'">
                                <div
                                    :ref="
                                        (element) => {
                                            chartHost =
                                                element as HTMLElement | null;
                                        }
                                    "
                                    class="min-h-80"
                                >
                                    <ProgressChart
                                        v-if="chartVisible"
                                        :entries="progressEntries"
                                        :goal="quantifiableGoal"
                                    />
                                </div>
                            </FeatureShowcase>
                            <FeatureShowcase v-else-if="id === 'streaks'"
                                ><RecurringProgress
                                    :item="recurringGoal"
                                    class="mb-6" /><EntryHeatmap
                                    v-bind="heatmap"
                            /></FeatureShowcase>
                            <FeatureShowcase v-else-if="id === 'milestones'">
                                <MultiStepProgress :item="multiStepGoal" />
                                <ol class="mt-6 space-y-4">
                                    <li
                                        v-for="step in multiStepGoal.milestones"
                                        :key="step.id"
                                        class="flex items-center gap-4"
                                    >
                                        <span
                                            class="flex size-8 shrink-0 items-center justify-center border-2"
                                            :class="
                                                step.is_completed
                                                    ? 'border-success bg-success text-success-foreground'
                                                    : 'border-border'
                                            "
                                            aria-hidden="true"
                                            ><Check
                                                v-if="step.is_completed"
                                                class="size-4" /></span
                                        ><span class="text-sm"
                                            >{{ $t(step.title)
                                            }}<span
                                                v-if="step.is_completed"
                                                class="sr-only"
                                                >:
                                                {{
                                                    $t(
                                                        'public.showcase.completed',
                                                    )
                                                }}</span
                                            ></span
                                        >
                                    </li>
                                </ol>
                            </FeatureShowcase>
                            <FeatureShowcase v-else-if="id === 'assistant'">
                                <div class="space-y-4" inert>
                                    <div
                                        v-for="message in conversation"
                                        :key="message.id"
                                        class="flex flex-col gap-2"
                                        :class="
                                            message.role === 'user'
                                                ? 'items-end'
                                                : 'items-start'
                                        "
                                    >
                                        <template
                                            v-for="(
                                                part, partIndex
                                            ) in message.parts"
                                            :key="partIndex"
                                        >
                                            <template
                                                v-if="
                                                    part.type === 'text' &&
                                                    part.text
                                                "
                                            >
                                                <p
                                                    v-if="
                                                        message.role === 'user'
                                                    "
                                                    class="max-w-[85%] bg-primary px-3 py-2 text-sm break-words whitespace-pre-wrap text-primary-foreground"
                                                >
                                                    {{ part.text }}
                                                </p>
                                                <AssistantMarkdown
                                                    v-else
                                                    :text="part.text"
                                                />
                                            </template>
                                            <AssistantToolActivity
                                                v-else-if="
                                                    part.type.startsWith(
                                                        'tool-',
                                                    )
                                                "
                                                :part="part"
                                                class="w-full"
                                            />
                                        </template>
                                    </div>
                                </div>
                            </FeatureShowcase>
                            <FeatureShowcase v-else-if="id === 'mcp'"
                                ><TerminalIllustration
                            /></FeatureShowcase>
                            <FeatureShowcase
                                v-else-if="id === 'make-it-yours'"
                                src="/images/showcase/themes.png"
                                :alt="$t('public.showcase.themes_alt')"
                                v-bind="showcaseImageDimensions.themes"
                            />
                            <FeatureShowcase v-else-if="id === 'your-data'">
                                <p
                                    class="flex items-center gap-3 text-sm font-semibold text-[var(--primary-text)]"
                                >
                                    <LockKeyhole
                                        class="size-5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ $t('public.showcase.encrypted') }}
                                </p>
                                <ul class="mt-5 divide-y border-y">
                                    <li
                                        v-for="field in [
                                            'goals',
                                            'notes',
                                            'categories',
                                            'keys',
                                        ]"
                                        :key="field"
                                        class="flex items-center gap-3 py-4 text-sm"
                                    >
                                        <Check
                                            class="size-4 shrink-0 text-[var(--primary-text)]"
                                            aria-hidden="true"
                                        />
                                        {{
                                            $t(
                                                `public.showcase.encrypted_${field}`,
                                            )
                                        }}
                                    </li>
                                </ul>
                            </FeatureShowcase>
                        </template>
                    </FeatureSection>
                    <section
                        id="faq"
                        aria-labelledby="faq-title"
                        class="scroll-mt-8 border-t pt-14 md:pt-20"
                    >
                        <p
                            class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            {{ $t('public.faq.label') }}
                        </p>
                        <h2
                            id="faq-title"
                            class="mt-3 font-display text-3xl font-semibold"
                        >
                            {{ $t('public.faq.title') }}
                        </h2>
                        <FaqAccordion :items="faq" class="mt-8" />
                    </section>
                    <ClosingCta />
                </div>
            </div>
        </main>
    </PublicShell>
</template>
