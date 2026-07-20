<script setup lang="ts">
import { getBinaryTheme } from '@/composables/useAppearance';
import { GoalEntry } from '@/types/models';
import { trans } from 'laravel-vue-i18n';
import { prefersReducedMotion, readCssVar } from './chart-utils';

const props = defineProps<{
    entries: Pick<GoalEntry, 'entry_date' | 'value'>[];
    targetValue: number | string | null;
    unit: string | null;
}>();

const theme = getBinaryTheme();

// Series 1 (progress) uses the brand colour; series 2 (target) stays muted.
const chartColors = [readCssVar('--chart-1'), readCssVar('--muted-foreground')];

const sortedEntries = [...props.entries].sort(
    (a, b) =>
        new Date(a.entry_date).getTime() - new Date(b.entry_date).getTime(),
);

const chartSeries = [
    {
        name: trans('goals.chart.values'),
        data: sortedEntries.map((item) => ({
            x: new Date(item.entry_date).getTime(),
            y: item.value,
        })),
    },
    {
        name: trans('goals.chart.target'),
        data: sortedEntries.map((item) => ({
            x: new Date(item.entry_date).getTime(),
            y: props.targetValue,
        })),
    },
];

const chartOptions = {
    colors: chartColors,
    chart: {
        height: 350,
        type: 'line',
        animations: {
            enabled: !prefersReducedMotion(),
        },
        zoom: {
            enabled: true,
        },
    },
    theme: {
        mode: theme,
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        curve: 'smooth',
        width: [3, 2],
        dashArray: [0, 6],
    },
    grid: {
        row: {
            opacity: 0.5,
        },
    },
    xaxis: {
        type: 'datetime',
        title: {
            text: trans('goals.chart.entry_date'),
        },
    },
};
</script>

<template>
    <apexchart
        type="line"
        width="100%"
        height="auto"
        :options="chartOptions"
        :series="chartSeries"
    ></apexchart>
</template>
