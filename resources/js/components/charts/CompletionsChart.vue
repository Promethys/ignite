<script setup lang="ts">
import {
    Empty,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useChartTheme } from '@/composables/useChartTheme';
import { barChartOptions } from '@/lib/chart-theme';
import { formatMonthLabels } from '@/lib/chart-utils';
import { MonthlyCompletionItem } from '@/types/charts';
import { wTrans } from 'laravel-vue-i18n';
import { TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    data: MonthlyCompletionItem[];
}>();

const isEmpty = computed(() => props.data.every((item) => item.count === 0));

const seriesName = wTrans('dashboard.charts.completions.series');

const chartSeries = computed(() => [
    {
        name: seriesName.value,
        data: props.data.map((item) => item.count),
    },
]);

const chartOptions = useChartTheme(() => ({
    ...barChartOptions(),
    xaxis: {
        ...(barChartOptions().xaxis as object),
        categories: formatMonthLabels(props.data.map((item) => item.month)),
    },
}));
</script>

<template>
    <apexchart
        v-if="!isEmpty"
        type="bar"
        width="100%"
        height="300"
        :options="chartOptions"
        :series="chartSeries"
    ></apexchart>
    <Empty v-else class="py-10">
        <EmptyTitle>
            <EmptyMedia class="mx-auto" variant="icon">
                <TrendingUp />
            </EmptyMedia>
            {{ $t('dashboard.charts.completions.empty.title') }}
        </EmptyTitle>
        <EmptyDescription>{{
            $t('dashboard.charts.completions.empty.description')
        }}</EmptyDescription>
    </Empty>
</template>
