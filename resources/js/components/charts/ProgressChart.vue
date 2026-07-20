<script setup lang="ts">
import { useChartTheme } from '@/composables/useChartTheme';
import { lineChartOptions } from '@/lib/chart-theme';
import { readCssVar } from '@/lib/chart-utils';
import { GoalEntry } from '@/types/models';
import { wTrans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    entries: Pick<GoalEntry, 'entry_date' | 'value'>[];
    targetValue: number | string | null;
    unit: string | null;
}>();

const valuesLabel = wTrans('goals.chart.values');
const targetLabel = wTrans('goals.chart.target');

const sortedEntries = computed(() =>
    [...props.entries].sort(
        (a, b) =>
            new Date(a.entry_date).getTime() - new Date(b.entry_date).getTime(),
    ),
);

const chartSeries = computed(() => [
    {
        name: valuesLabel.value,
        data: sortedEntries.value.map((item) => ({
            x: new Date(item.entry_date).getTime(),
            y: item.value,
        })),
    },
]);

const target = computed(() =>
    props.targetValue === null ? null : Number(props.targetValue),
);

const chartOptions = useChartTheme(() => ({
    ...lineChartOptions(),
    xaxis: {
        ...(lineChartOptions().xaxis as object),
        type: 'datetime',
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
                              position: 'left',
                              textAnchor: 'start',
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
}));
</script>

<template>
    <apexchart
        type="line"
        width="100%"
        height="320"
        :options="chartOptions"
        :series="chartSeries"
    ></apexchart>
</template>
