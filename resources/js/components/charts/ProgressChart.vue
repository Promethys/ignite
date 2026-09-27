<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useChartTheme } from '@/composables/useChartTheme';
import { lineChartOptions } from '@/lib/chart-theme';
import { readCssVar } from '@/lib/chart-utils';
import { GoalEntry } from '@/types/models';
import { wTrans } from 'laravel-vue-i18n';
import moment from 'moment';
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    entries: Pick<GoalEntry, 'entry_date' | 'value'>[];
    targetValue: number | string | null;
    unit: string | null;
}>();

type TimeRange = '1m' | '3m' | '1y' | 'all';

const TIME_RANGES: TimeRange[] = ['1m', '3m', '1y', 'all'];

const MONTHS_IN_RANGE: Record<Exclude<TimeRange, 'all'>, number> = {
    '1m': 1,
    '3m': 3,
    '1y': 12,
};

const timeRange = ref<TimeRange>('3m');

const valuesLabel = wTrans('goals.chart.values');

const points = computed(() =>
    props.entries
        .map((entry) => ({
            x: new Date(entry.entry_date).getTime(),
            y: Number(entry.value),
        }))
        .sort((a, b) => a.x - b.x),
);

const firstDate = computed(() => points.value[0]?.x ?? 0);
const lastDate = computed(() => points.value.at(-1)?.x ?? 0);

const spansMoreThanShortestRange = computed(
    () =>
        firstDate.value <
        moment
            .utc(lastDate.value)
            .subtract(MONTHS_IN_RANGE['1m'], 'months')
            .valueOf(),
);

const windowStart = computed(() => {
    if (timeRange.value === 'all') return firstDate.value;

    const start = moment
        .utc(lastDate.value)
        .subtract(MONTHS_IN_RANGE[timeRange.value], 'months')
        .valueOf();

    return Math.max(start, firstDate.value);
});

const target = computed(() =>
    props.targetValue === null ? null : Number(props.targetValue),
);

const targetLabel = computed(() => {
    if (target.value === null) return '';

    const value = [target.value.toLocaleString(), props.unit]
        .filter(Boolean)
        .join(' ');

    return wTrans('goals.chart.target', { value }).value;
});

const yAxisBounds = computed(() => {
    const values = [
        ...points.value
            .filter((point) => point.x >= windowStart.value)
            .map((point) => point.y),
        ...(target.value === null ? [] : [target.value]),
    ];

    if (values.length === 0) return null;

    const lowest = Math.min(...values);
    const highest = Math.max(...values);
    const padding = (highest - lowest) * 0.05 || Math.abs(highest) * 0.05 || 1;

    return {
        min: lowest >= 0 ? Math.max(0, lowest - padding) : lowest - padding,
        max: highest + padding,
    };
});

const chartSeries = computed(() => [
    { name: valuesLabel.value, data: points.value },
]);

const chartOptions = useChartTheme(() => {
    const base = lineChartOptions();
    const hasSpan = lastDate.value > firstDate.value;

    return {
        ...base,
        xaxis: {
            ...(base.xaxis as object),
            type: 'datetime',
            ...(hasSpan ? { min: windowStart.value, max: lastDate.value } : {}),
        },
        yaxis: {
            ...(base.yaxis as object),
            ...(yAxisBounds.value ?? {}),
            forceNiceScale: true,
        },
        annotations: {
            yaxis:
                target.value === null
                    ? []
                    : [
                          {
                              y: target.value,
                              borderColor: readCssVar('--muted-foreground'),
                              strokeDashArray: 5,
                              label: {
                                  text: targetLabel.value,
                                  position: 'right',
                                  textAnchor: 'end',
                                  borderWidth: 0,
                                  style: {
                                      background: 'transparent',
                                      color: readCssVar('--muted-foreground'),
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
    <div class="space-y-3">
        <Tabs
            v-if="spansMoreThanShortestRange"
            v-model="timeRange"
            class="items-end"
        >
            <TabsList :aria-label="$t('goals.chart.range.label')">
                <TabsTrigger
                    v-for="range in TIME_RANGES"
                    :key="range"
                    :value="range"
                >
                    {{ $t(`goals.chart.range.${range}`) }}
                </TabsTrigger>
            </TabsList>
        </Tabs>
        <VueApexCharts
            type="line"
            width="100%"
            height="320"
            :options="chartOptions"
            :series="chartSeries"
        />
    </div>
</template>
