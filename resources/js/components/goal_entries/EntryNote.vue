<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{ note: string; textClass?: string }>(),
    { textClass: 'text-sm' },
);

const expanded = ref(false);
const overflows = ref(false);
const noteElement = ref<HTMLElement | null>(null);

const measureOverflow = () => {
    const element = noteElement.value;

    if (!element) {
        return;
    }

    overflows.value = element.scrollHeight > element.clientHeight + 1;
};

onMounted(measureOverflow);

watch(
    () => props.note,
    () => {
        expanded.value = false;
        requestAnimationFrame(measureOverflow);
    },
);
</script>

<template>
    <div class="space-y-1">
        <p
            ref="noteElement"
            class="whitespace-pre-line"
            :class="[textClass, expanded ? '' : 'line-clamp-3']"
        >
            {{ note }}
        </p>
        <button
            v-if="overflows"
            type="button"
            class="text-xs text-muted-foreground underline underline-offset-2 hover:text-foreground"
            @click="expanded = !expanded"
        >
            {{
                expanded
                    ? $t('goals.entries.note_show_less')
                    : $t('goals.entries.note_show_more')
            }}
        </button>
    </div>
</template>
