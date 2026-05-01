<script setup lang="ts">
import { toTitleCase } from '@/lib/utils';
import { Goal } from '@/types/models';
import Badge from '../ui/badge/Badge.vue';

const props = defineProps<{
    goal: Goal;
}>();

const getStatusDisplayName = function (name: string) {
    return toTitleCase(name.replace('_', ' '));
};

const goalStatusBadgeColors = {
    'bg-green-700 text-white': props.goal.status === 'completed',
    'bg-yellow-300 text-black': props.goal.status === 'paused',
    'bg-orange-500 text-white': props.goal.status === 'abandoned',
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Badge
            class="rounded-full text-2xs font-semibold"
            :class="goalStatusBadgeColors"
            >{{ getStatusDisplayName(goal.status) }}</Badge
        >
        <Badge
            class="rounded-full text-2xs font-semibold"
            v-if="goal.category?.name"
            >{{ goal.category?.name }}</Badge
        >
        <Badge class="rounded-full text-2xs font-semibold">{{
            toTitleCase(goal.priority) + ' Priority'
        }}</Badge>
        <Badge
            class="rounded-full text-2xs font-semibold"
            v-if="goal.recurrence"
            >{{ toTitleCase(goal.recurrence) }}</Badge
        >
    </div>
</template>
