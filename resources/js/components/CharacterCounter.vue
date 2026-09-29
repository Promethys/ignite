<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const WARNING_RATIO = 0.8;

const props = defineProps<{
    value: string | null | undefined;
    max: number;
}>();

const count = computed(() => [...(props.value ?? '')].length);
const isVisible = computed(() => count.value >= props.max * WARNING_RATIO);
const isOverLimit = computed(() => count.value > props.max);
</script>

<template>
    <span
        aria-live="polite"
        :class="
            cn(
                'text-xs font-medium tabular-nums',
                isOverLimit ? 'text-destructive' : 'text-warning',
            )
        "
    >
        <template v-if="isVisible">
            <span aria-hidden="true">{{ count }}/{{ max }}</span>
            <span class="sr-only">{{
                $t('common.form.character_count', {
                    count: count.toString(),
                    max: max.toString(),
                })
            }}</span>
        </template>
    </span>
</template>
