<script setup lang="ts">
import { Goal } from '@/types/models';
import { computed } from 'vue';
import { Flame } from 'lucide-vue-next';

const props = defineProps<{ item: Goal }>();

const slots = 7;

const unitByRecurrence: Record<NonNullable<Goal['recurrence']>, string> = {
    daily: 'day',
    weekly: 'week',
    monthly: 'month',
    annually: 'year',
};

const streak = computed(() => props.item.streak);
const current = computed(() => streak.value?.current ?? 0);
const unit = computed(
    () => streak.value?.unit ?? unitByRecurrence[props.item.recurrence ?? 'daily'] ?? 'day',
);
const hasStreak = computed(() => current.value > 0);
const filledDots = computed(() => Math.min(current.value, slots));
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-1.5 text-sm font-medium">
            <Flame class="size-4" :class="hasStreak ? 'text-primary' : 'text-muted-foreground'" />
            <template v-if="hasStreak">{{ current }}-{{ unit }} streak</template>
            <template v-else>No active streak</template>
        </div>
        <div class="flex gap-1">
            <span
                v-for="i in slots"
                :key="i"
                class="size-2 rounded-full"
                :class="i <= filledDots ? 'bg-primary' : 'bg-muted'"
            />
        </div>
    </div>
</template>
