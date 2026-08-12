<script setup lang="ts">
const corners = [
    'top-[-1px] left-[-1px] border-r-0 border-b-0',
    'top-[-1px] right-[-1px] border-l-0 border-b-0',
    'bottom-[-1px] left-[-1px] border-r-0 border-t-0',
    'bottom-[-1px] right-[-1px] border-l-0 border-t-0',
];
</script>

<template>
    <div class="relative aspect-video w-full border bg-card">
        <span
            v-for="corner in corners"
            :key="corner"
            :class="corner"
            class="absolute size-2 border border-primary"
            aria-hidden="true"
        />
        <slot />
    </div>
</template>
