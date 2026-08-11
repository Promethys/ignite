<script setup lang="ts">
const features = [
    'types',
    'visual',
    'streaks',
    'milestones',
    'categories',
    'mcp',
    'selfhost',
    'i18n',
];

const rule =
    'relative before:absolute before:top-0 before:left-[-100vw] before:h-px before:w-[200vw] before:bg-border';
</script>

<template>
    <section id="features">
        <div
            :class="rule"
            class="flex w-full items-baseline justify-between pt-7 pb-3 font-mono text-xs tracking-widest text-muted-foreground uppercase"
        >
            <span class="font-semibold">
                {{ $t('landing.features.section_title') }}
            </span>
            <span>{{ features.length }}</span>
        </div>

        <div>
            <div
                v-for="(feature, index) in features"
                :key="feature"
                :class="rule"
                class="grid grid-cols-[3rem_1fr] gap-x-5 py-6 sm:grid-cols-[4rem_16rem_1fr] sm:gap-x-6"
            >
                <span
                    class="font-mono text-xs text-muted-foreground tabular-nums"
                >
                    {{ String(index + 1).padStart(2, '0') }}
                </span>
                <h4 class="font-display text-base leading-tight font-semibold">
                    {{ $t(`landing.features.${feature}.title`) }}
                </h4>
                <p
                    class="col-start-2 mt-2 text-sm text-muted-foreground sm:col-start-3 sm:mt-0"
                >
                    {{ $t(`landing.features.${feature}.description`) }}
                </p>
            </div>
        </div>
    </section>
</template>
