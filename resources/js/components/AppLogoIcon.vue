<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        monochrome?: boolean;
    }>(),
    {
        monochrome: false,
    },
);
</script>

<template>
    <svg
        v-bind="$attrs"
        viewBox="0 0 32 32"
        fill="none"
        stroke-linejoin="miter"
    >
        <path
            d="M6 29 16 14 26 29"
            :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-base)'"
            stroke-width="5.6"
        />
        <path
            d="M10.2 18.5 16 9.8 21.8 18.5"
            :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-mid)'"
            stroke-width="3.7"
        />
        <path
            d="M13.2 9.6 16 5.4 18.8 9.6"
            :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-tip)'"
            stroke-width="2.3"
        />
    </svg>
</template>
