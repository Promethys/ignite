<script setup lang="ts">
import { getBinaryTheme } from '@/composables/useAppearance';
import { GoalEntry } from '@/types/models';

const props = defineProps<{
    entries: Pick<GoalEntry, 'entry_date' | 'value'>[];
    targetValue: number | string | null;
    unit: string | null;
}>();

const theme = getBinaryTheme();

const cssVar = (name: string) =>
    typeof document !== 'undefined'
        ? getComputedStyle(document.documentElement)
              .getPropertyValue(name)
              .trim()
        : '';

// Series 1 (progress) uses the brand colour; series 2 (target) stays muted.
const chartColors = [cssVar('--chart-1'), cssVar('--muted-foreground')];

const sortedEntries = [...props.entries].sort(
    (a, b) =>
        new Date(a.entry_date).getTime() - new Date(b.entry_date).getTime(),
);

const chartSeries = [
    {
        name: 'Values',
        data: sortedEntries.map((item) => ({
            x: new Date(item.entry_date).getTime(),
            y: item.value,
        })),
    },
    {
        name: 'Target Value',
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
            text: 'Entry date',
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
