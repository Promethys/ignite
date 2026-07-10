<script setup lang="ts">
import { streakUnit } from '@/lib/streak';
import { Goal } from '@/types/models';
import { Flame } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ item: Goal }>();

const slots = 7;

const streak = computed(() => props.item.streak);
const current = computed(() => streak.value?.current ?? 0);
const unit = computed(() => streakUnit(props.item));
const isNegative = computed(() => props.item.polarity === 'negative');
const hasStreak = computed(() => current.value > 0);
const filledDots = computed(() => Math.min(current.value, slots));
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-1.5 text-sm font-medium">
            <Flame
                class="size-4"
                :class="hasStreak ? 'text-primary' : 'text-muted-foreground'"
            />
            <template v-if="isNegative">{{
                $tChoice(`goals.streak.negative.${unit}`, current, {
                    count: current.toString(),
                })
            }}</template>
            <template v-else-if="hasStreak">{{
                $tChoice(`goals.streak.positive.${unit}`, current, {
                    count: current.toString(),
                })
            }}</template>
            <template v-else>{{ $t('goals.streak.none') }}</template>
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
