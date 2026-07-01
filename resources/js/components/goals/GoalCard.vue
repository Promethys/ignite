<script setup lang="ts">
import { Goal } from '@/types/models';
import BaseGoalCard from './BaseGoalCard.vue';
import MultiStepProgress from './progress/MultiStepProgress.vue';
import QuantifiableProgress from './progress/QuantifiableProgress.vue';
import RecurringProgress from './progress/RecurringProgress.vue';

defineProps<{ item: Goal }>();
</script>

<template>
    <BaseGoalCard :item="item">
        <template #progress>
            <QuantifiableProgress
                v-if="item.type === 'quantifiable'"
                :item="item"
            />
            <RecurringProgress
                v-else-if="item.type === 'recurring'"
                :item="item"
            />
            <MultiStepProgress
                v-else-if="item.type === 'multi_step'"
                :item="item"
            />
            <!-- simple: no progress zone -->
        </template>
    </BaseGoalCard>
</template>
