<script setup lang="ts">
import { Goal } from '@/types/models';
import Badge from '../ui/badge/Badge.vue';
import StatusDot from '../ui/badge/StatusDot.vue';

defineProps<{
    goal: Goal;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Badge class="gap-1.5 text-2xs font-medium">
            <StatusDot :status="goal.status" />
            {{ $t(`goals.statuses.${goal.status}`) }}
        </Badge>
        <Badge v-if="goal.category?.name" class="text-2xs font-medium">
            {{ goal.category.name }}
        </Badge>
        <Badge class="text-2xs font-medium">
            {{
                $t('goals.badges.priority', {
                    level: $t(`goals.priorities.${goal.priority}`),
                })
            }}
        </Badge>
        <Badge v-if="goal.recurrence" class="text-2xs font-medium">
            {{ $t(`goals.recurrences.${goal.recurrence}`) }}
        </Badge>
    </div>
</template>
