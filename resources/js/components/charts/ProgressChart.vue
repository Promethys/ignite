<script setup lang="ts">
import { getBinaryTheme } from '@/composables/useAppearance';
import { GoalEntry } from '@/types/models';

const props = defineProps<{
    entries: GoalEntry[];
    targetValue: number | string | null;
    unit: string | null;
}>();

const theme = getBinaryTheme();

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
