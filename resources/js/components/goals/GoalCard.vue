<script setup lang="ts">
import { Goal } from '@/types/models';
import SimpleGoalCard from './SimpleGoalCard.vue';
import QuantifiableGoalCard from './QuantifiableGoalCard.vue';
import RecurringGoalCard from './RecurringGoalCard.vue';
import MultiStepGoalCard from './MultiStepGoalCard.vue';

const props = defineProps<{
    item: Goal;
}>();
</script>

<template>
    <SimpleGoalCard v-if="item.type === 'simple'" :item="item" />
    <QuantifiableGoalCard v-else-if="item.type === 'quantifiable'" :item="item" />
    <RecurringGoalCard v-else-if="item.type === 'recurring'" :item="item" />
    <MultiStepGoalCard v-else-if="item.type === 'multi_step'" :item="item" />
</template>
