<script setup lang="ts">
import { toTitleCase } from '@/lib/utils';
import { Goal } from '@/types/models';
import Badge from '../ui/badge/Badge.vue';
import StatusDot from '../ui/badge/StatusDot.vue';

const props = defineProps<{
    goal: Goal;
}>();

const statusLabel = toTitleCase(props.goal.status.replace('_', ' '));
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Badge class="gap-1.5 text-2xs font-medium">
            <StatusDot :status="goal.status" />
            {{ statusLabel }}
        </Badge>
        <Badge v-if="goal.category?.name" class="text-2xs font-medium">
            {{ goal.category.name }}
        </Badge>
        <Badge class="text-2xs font-medium">
            {{ toTitleCase(goal.priority) }} Priority
        </Badge>
        <Badge v-if="goal.recurrence" class="text-2xs font-medium">
            {{ toTitleCase(goal.recurrence) }}
        </Badge>
    </div>
</template>
