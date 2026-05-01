<script setup lang="ts">
import { Goal } from '@/types/models';
import MultiStepGoalCard from './MultiStepGoalCard.vue';
import QuantifiableGoalCard from './QuantifiableGoalCard.vue';
import RecurringGoalCard from './RecurringGoalCard.vue';
import SimpleGoalCard from './SimpleGoalCard.vue';

defineProps<{
    item: Goal;
}>();
</script>

<template>
    <SimpleGoalCard v-if="item.type === 'simple'" :item="item" />
    <QuantifiableGoalCard
        v-else-if="item.type === 'quantifiable'"
        :item="item"
    />
    <RecurringGoalCard v-else-if="item.type === 'recurring'" :item="item" />
    <MultiStepGoalCard v-else-if="item.type === 'multi_step'" :item="item" />
</template>
