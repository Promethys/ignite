<script setup lang="ts">
import { Goal } from '@/types/models';
import { computed } from 'vue';

const props = defineProps<{ item: Goal }>();

const total = computed(() => props.item.milestones?.length ?? 0);
const done = computed(
    () => props.item.milestones?.filter((m) => m.is_completed).length ?? 0,
);
</script>

<template>
    <div v-if="total > 0" class="space-y-2">
        <div class="flex gap-1">
            <span
                v-for="(m, i) in item.milestones"
                :key="m.id ?? i"
                class="h-1.5 flex-1 rounded-full"
                :class="m.is_completed ? 'bg-primary' : 'bg-muted'"
            />
        </div>
        <span class="text-xs text-muted-foreground">{{
            $tChoice('goals.progress.steps', total, {
                done: done.toString(),
                total: total.toString(),
            })
        }}</span>
    </div>
</template>
