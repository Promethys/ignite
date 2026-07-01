<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
    >
        <div>
            <h1
                class="font-display text-2xl font-semibold tracking-tight text-balance sm:text-3xl"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-1 text-sm text-pretty text-muted-foreground sm:text-base"
            >
                {{ description }}
            </p>
        </div>
        <div class="shrink-0 sm:mt-1">
            <slot name="actions" />
        </div>
    </div>
</template>
