<script setup lang="ts">
import { useChartTheme } from '@/composables/useChartTheme';
import { lineChartOptions } from '@/lib/chart-theme';
import { readCssVar } from '@/lib/chart-utils';
import type { Goal } from '@/types/models';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    entries: Pick<
        NonNullable<Goal['entries']>[number],
        'entry_date' | 'value'
    >[];
    goal: Goal;
}>();

const series = computed(() => [
    {
        name: trans('goals.chart.values'),
        data: props.entries.map((entry) => ({
            x: new Date(entry.entry_date).getTime(),
            y: Number(entry.value),
        })),
    },
]);
const options = useChartTheme(() => {
    const base = lineChartOptions();
    const yaxis = Array.isArray(base.yaxis) ? base.yaxis[0] : base.yaxis;
    const muted = readCssVar('--muted-foreground');

    return {
        ...base,
        xaxis: { ...base.xaxis, type: 'datetime' },
        yaxis: {
            ...yaxis,
            min: 0,
            max: 400,
            tickAmount: 4,
            forceNiceScale: false,
            title: {
                text: trans('public.showcase.distance_axis'),
                style: { color: muted, fontWeight: 400 },
            },
        },
        annotations: {
            yaxis: [
                {
                    y: Number(props.goal.target_value),
                    borderColor: muted,
                    strokeDashArray: 5,
                    label: {
                        text: trans('goals.chart.target', { value: '400 km' }),
                        position: 'right',
                        textAnchor: 'end',
                        borderWidth: 0,
                        style: {
                            background: 'transparent',
                            color: muted,
                            fontSize: '11px',
                        },
                    },
                },
            ],
        },
    };
});
</script>

<template>
    <VueApexCharts
        type="line"
        width="100%"
        height="320"
        :options="options"
        :series="series"
    />
</template>
