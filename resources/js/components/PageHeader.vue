<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
    >
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-balance sm:text-3xl"
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
        <slot name="actions" />
    </div>
</template>
