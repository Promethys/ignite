<script setup lang="ts">
import { Progress } from '@/components/ui/progress';
import { Goal } from '@/types/models';

const props = defineProps<{ item: Goal }>();
</script>

<template>
    <div v-if="props.item.target_value" class="space-y-2">
        <Progress :model-value="props.item.progress_percentage" class="h-1.5" />
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold"
                >{{ Math.round(props.item.progress_percentage) }}%</span
            >
            <span class="text-muted-foreground">
                {{ props.item.current_value }}
                {{ props.item.direction === 'ascending' ? '/' : '→' }}
                {{ props.item.target_value }} {{ props.item.unit }}
            </span>
        </div>
    </div>
</template>
