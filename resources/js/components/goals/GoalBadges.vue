<script setup lang="ts">
import { Goal } from '@/types/models';
import Badge from '../ui/badge/Badge.vue';
import { toTitleCase } from '@/lib/utils';

const props = defineProps<{
    goal: Goal;
}>();

const getStatusDisplayName = function (name: string) {
    return toTitleCase(name.replace('_', ' '));
}

const goalStatusBadgeColors = {
    'bg-green-700 text-white': props.goal.status === 'completed',
    'bg-yellow-300 text-black': props.goal.status === 'paused',
    'bg-orange-500 text-white': props.goal.status === 'abandoned',
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Badge class="rounded-full font-semibold text-2xs" :class="goalStatusBadgeColors">{{ getStatusDisplayName(goal.status) }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs" v-if="goal.category?.name">{{ goal.category?.name }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs">{{ toTitleCase(goal.priority) + ' Priority' }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs" v-if="goal.recurrence">{{ toTitleCase(goal.recurrence) }}</Badge>
    </div>
</template>
