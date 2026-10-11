<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { contrastingTextColor } from '@/lib/contrasting-text-color';
import { computed } from 'vue';

const props = defineProps<{
    lightColor: string;
    darkColor: string;
}>();

const panes = computed(() => [
    {
        mode: 'light',
        label: 'settings.themes.preview_light',
        surface: 'bg-white text-zinc-600',
        color: props.lightColor,
    },
    {
        mode: 'dark',
        label: 'settings.themes.preview_dark',
        surface: 'dark bg-background text-muted-foreground',
        color: props.darkColor,
    },
]);
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div
            v-for="pane in panes"
            :key="pane.mode"
            :class="pane.surface"
            :style="{
                '--primary': pane.color,
                '--primary-foreground': contrastingTextColor(pane.color),
            }"
            :data-test="`theme-preview-${pane.mode}`"
            class="flex flex-col gap-3 border p-3"
        >
            <span class="text-xs font-semibold tracking-widest uppercase">
                {{ $t(pane.label) }}
            </span>
            <Button type="button" size="sm" tabindex="-1" class="w-fit">
                {{ $t('settings.themes.preview_button') }}
            </Button>
            <div class="h-1.5 w-full bg-primary/20">
                <div class="h-full w-2/3 bg-primary" />
            </div>
            <span class="text-sm text-primary underline underline-offset-4">
                {{ $t('settings.themes.preview_link') }}
            </span>
        </div>
    </div>
</template>
