<script setup lang="ts">
import { Goal } from '@/types/models';
import Badge from '../ui/badge/Badge.vue';
import { toTitleCase } from '@/lib/utils';

defineProps<{
    goal: Goal;
}>();

const getStatusDisplayName = function (name: string) {
    return toTitleCase(name.replace('_', ' '));
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Badge class="rounded-full font-semibold text-2xs" :class="{'bg-green-700 text-white': goal.status === 'completed'}">{{ getStatusDisplayName(goal.status) }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs" v-if="goal.category?.name">{{ goal.category?.name }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs">{{ toTitleCase(goal.priority) + ' Priority' }}</Badge>
        <Badge class="rounded-full font-semibold text-2xs" v-if="goal.recurrence">{{ toTitleCase(goal.recurrence) }}</Badge>
    </div>
</template>
