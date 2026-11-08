<script setup lang="ts">
defineProps<{ label: string; title: string; lead?: string }>();
</script>

<template>
    <div class="max-w-3xl">
        <p
            class="flex items-center gap-2 text-xs font-semibold tracking-widest text-[var(--primary-text)] uppercase"
        >
            <span class="size-2 shrink-0 bg-primary" aria-hidden="true" />{{
                label
            }}
        </p>
        <h1
            class="mt-4 font-display text-4xl leading-tight font-bold text-balance sm:text-5xl"
        >
            {{ title }}
        </h1>
        <p
            v-if="lead"
            class="mt-5 text-lg leading-relaxed text-pretty text-muted-foreground"
        >
            {{ lead }}
        </p>
    </div>
</template>
