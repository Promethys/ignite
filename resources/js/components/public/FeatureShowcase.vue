<script setup lang="ts">
defineProps<{
    src?: string;
    alt?: string;
    width?: number;
    height?: number;
    caption?: string;
}>();
</script>

<template>
    <figure class="min-w-0 border bg-card">
        <img
            v-if="src"
            :src="src"
            :alt="alt ?? ''"
            :width="width"
            :height="height"
            loading="lazy"
            class="h-auto w-full"
        />
        <div v-else class="p-5 sm:p-6"><slot /></div>
        <figcaption
            v-if="caption"
            class="border-t px-5 py-3 text-xs text-muted-foreground"
        >
            {{ caption }}
        </figcaption>
    </figure>
</template>
