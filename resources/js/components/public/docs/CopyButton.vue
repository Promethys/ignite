<script setup lang="ts">
import { useClipboard } from '@vueuse/core';
import { trans } from 'laravel-vue-i18n';
import { Check, Copy, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ code: string }>();
const { copy, copied, isSupported } = useClipboard({ copiedDuring: 2500 });
const failed = ref(false);
const label = computed(() =>
    trans(
        copied.value
            ? 'public.docs.copied'
            : failed.value
              ? 'public.docs.copy_failed'
              : 'public.docs.copy',
    ),
);
const copyCode = async () => {
    failed.value = !isSupported.value;
    if (failed.value) return;
    try {
        await copy(props.code);
    } catch {
        failed.value = true;
    }
};
</script>

<template>
    <button
        type="button"
        class="docs-copy-button"
        :aria-label="label"
        :title="label"
        aria-live="polite"
        @click="copyCode"
    >
        <component
            :is="copied ? Check : failed ? TriangleAlert : Copy"
            class="size-4"
            aria-hidden="true"
        />
        <span class="sr-only">{{ label }}</span>
    </button>
</template>
