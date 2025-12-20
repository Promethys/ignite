<script setup lang="ts">
import { Goal } from '@/types/models';
import Badge from './ui/badge/Badge.vue';
import { toTitleCase } from '@/lib/utils';

defineProps<{
    goal: Goal;
}>();

const getStatusDisplayName = function (name: string) {
    return toTitleCase(name.replace('_', ' '));
}
</script>

<template>
    <div class="flex items-center gap-2">
        <Badge class="rounded-full font-semibold" v-if="goal.category?.name">{{ goal.category?.name }}</Badge>
        <Badge class="rounded-full font-semibold">{{ getStatusDisplayName(goal.status) }}</Badge>
        <Badge class="rounded-full font-semibold">{{ toTitleCase(goal.priority) + ' Priority' }}</Badge>
        <Badge class="rounded-full font-semibold" v-if="goal.recurrence">{{ toTitleCase(goal.recurrence) }}</Badge>
    </div>
</template>
