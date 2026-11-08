<script setup lang="ts">
import { features as featuresRoute } from '@/routes';
import { Link } from '@inertiajs/vue3';
const anchors: Record<string, string> = {
    types: 'goal-types',
    visual: 'progress',
    streaks: 'streaks',
    milestones: 'milestones',
    categories: 'make-it-yours',
    assistant: 'assistant',
    mcp: 'mcp',
    selfhost: 'your-data',
    i18n: 'make-it-yours',
};
const features = [
    'types',
    'visual',
    'streaks',
    'milestones',
    'categories',
    'assistant',
    'mcp',
    'selfhost',
    'i18n',
];
</script>

<template>
    <section id="features">
        <div
            class="relative flex w-full items-baseline justify-between pt-7 pb-3 text-xs font-semibold tracking-widest text-muted-foreground uppercase before:absolute before:top-0 before:left-[-100vw] before:h-px before:w-[200vw] before:bg-border"
        >
            <h2>{{ $t('landing.features.section_title') }}</h2>
            <span class="tabular-nums">{{ features.length }}</span>
        </div>

        <ul
            class="grid grid-cols-1 gap-px border bg-border sm:grid-cols-2 lg:grid-cols-3"
        >
            <li
                v-for="feature in features"
                :key="feature"
                class="relative flex flex-col gap-2 bg-background px-5 pt-5 pb-6 outline-1 -outline-offset-1 outline-transparent focus-within:bg-card focus-within:outline-[var(--primary-text)] hover:bg-card hover:outline-[var(--primary-text)]"
            >
                <h3
                    class="font-display text-lg leading-tight font-semibold text-balance"
                >
                    <Link
                        :href="featuresRoute().url + '#' + anchors[feature]"
                        class="after:absolute after:inset-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                        >{{ $t(`landing.features.${feature}.title`) }}</Link
                    >
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ $t(`landing.features.${feature}.description`) }}
                </p>
            </li>
        </ul>
    </section>
</template>
