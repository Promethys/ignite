<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref, useAttrs } from 'vue';
import { useVModel } from '@vueuse/core';
import { Eye, EyeOff } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

const attrs = useAttrs();
const shown = ref(false);
const inputType = computed(() => (shown.value ? 'text' : 'password'));

const toggleShown = () => {
    shown.value = !shown.value;
};

const forwardedAttrs = computed(() => {
    const { type, ...rest } = attrs as Record<string, unknown>;
    return rest;
});
</script>

<template>
    <div :class="cn('relative w-full', props.class)">
        <input
            v-model="modelValue"
            data-slot="input"
            v-bind="forwardedAttrs"
            :type="inputType"
            :class="cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent py-1 pr-10 pl-3 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            )"
        >
        <button
            type="button"
            class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex w-9 items-center justify-center transition-colors"
            :aria-label="shown ? $t('common.password.hide') : $t('common.password.show')"
            :aria-pressed="shown"
            @click="toggleShown"
        >
            <component :is="shown ? EyeOff : Eye" class="h-4 w-4" />
        </button>
    </div>
</template>

