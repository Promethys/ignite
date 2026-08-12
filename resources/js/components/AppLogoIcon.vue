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
        class="app-logo-icon group/logo overflow-visible"
    >
        <g
            class="transition-transform duration-[340ms] ease-[cubic-bezier(.2,.8,.25,1)] motion-safe:group-hover/logo:-translate-y-px"
        >
            <path
                d="M6 29 16 14 26 29"
                stroke-width="5.6"
                class="transition-[stroke] duration-300 ease-out"
                :class="
                    monochrome
                        ? undefined
                        : 'group-hover/logo:stroke-[var(--brand-mark-base-hover)]'
                "
                :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-base)'"
            />
        </g>
        <g
            class="transition-transform delay-[50ms] duration-[340ms] ease-[cubic-bezier(.2,.8,.25,1)] motion-safe:group-hover/logo:-translate-y-[2.2px]"
        >
            <path
                d="M10.2 18.5 16 9.8 21.8 18.5"
                stroke-width="3.7"
                class="transition-[stroke] delay-[50ms] duration-300 ease-out motion-safe:group-hover/logo:animate-mark-settle-mid"
                :class="
                    monochrome
                        ? undefined
                        : 'group-hover/logo:stroke-[var(--brand-mark-mid-hover)]'
                "
                :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-mid)'"
            />
        </g>
        <g
            class="transition-transform delay-100 duration-[340ms] ease-[cubic-bezier(.2,.8,.25,1)] motion-safe:group-hover/logo:-translate-y-[3.6px]"
        >
            <path
                d="M13.2 9.6 16 5.4 18.8 9.6"
                stroke-width="2.3"
                class="transition-[stroke] delay-100 duration-300 ease-out motion-safe:group-hover/logo:animate-mark-settle-tip"
                :class="
                    monochrome
                        ? undefined
                        : 'group-hover/logo:stroke-[var(--brand-mark-tip-hover)]'
                "
                :stroke="monochrome ? 'currentColor' : 'var(--brand-mark-tip)'"
            />
        </g>
    </svg>
</template>
