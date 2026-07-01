<script setup lang="ts">
import type { Goal } from '@/types/models';

const props = defineProps<{ status: Goal['status'] }>();

const colorClass: Record<Goal['status'], string> = {
    in_progress: 'bg-primary',
    paused: 'bg-warning',
    completed: 'bg-success',
    not_started: 'bg-muted-foreground',
    abandoned: 'bg-muted-foreground',
};
</script>

<template>
    <span
        class="inline-block size-1.5 shrink-0 rounded-full"
        :class="colorClass[props.status]"
    />
</template>
