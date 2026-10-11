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
</script>

<template>
    <section id="features">
        <div
            class="relative flex w-full items-baseline justify-between pt-7 pb-3 text-xs font-semibold tracking-widest text-muted-foreground uppercase before:absolute before:top-0 before:left-[-100vw] before:h-px before:w-[200vw] before:bg-border"
        >
            <span>{{ $t('landing.features.section_title') }}</span>
            <span class="tabular-nums">{{ features.length }}</span>
        </div>

        <ul
            class="grid grid-cols-1 gap-px border bg-border sm:grid-cols-2 lg:grid-cols-4"
        >
            <li
                v-for="feature in features"
                :key="feature"
                class="flex flex-col gap-2 bg-background px-5 pt-5 pb-6"
            >
                <h4
                    class="font-display text-lg leading-tight font-semibold text-balance"
                >
                    {{ $t(`landing.features.${feature}.title`) }}
                </h4>
                <p class="text-sm text-muted-foreground">
                    {{ $t(`landing.features.${feature}.description`) }}
                </p>
            </li>
        </ul>
    </section>
</template>
