<script setup lang="ts">
import {
    Empty,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useChartTheme } from '@/composables/useChartTheme';
import { donutChartOptions } from '@/lib/chart-theme';
import { CategoryBreakdownItem } from '@/types/charts';
import { useElementSize } from '@vueuse/core';
import { wTrans } from 'laravel-vue-i18n';
import { PieChart } from 'lucide-vue-next';
import { computed, useTemplateRef } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    data: CategoryBreakdownItem[];
}>();

const isEmpty = computed(() => props.data.length === 0);

const chartSeries = computed(() =>
    props.data.map((item: CategoryBreakdownItem) => item.count),
);

const totalLabel = wTrans('dashboard.charts.categories.total');

const LEGEND_BELOW_WIDTH = 480;

const { width: chartWidth } = useElementSize(
    useTemplateRef<HTMLElement>('chartContainer'),
);

const isLegendBelow = computed(
    () => chartWidth.value > 0 && chartWidth.value < LEGEND_BELOW_WIDTH,
);

const chartOptions = useChartTheme(() => {
    const base = donutChartOptions();
    const baseLabels = base.plotOptions?.pie?.donut?.labels ?? {};

    return {
        ...base,
        legend: isLegendBelow.value
            ? { ...base.legend, position: 'bottom', horizontalAlign: 'center' }
            : base.legend,
        colors: props.data.map((item: CategoryBreakdownItem) => item.color),
        labels: props.data.map((item: CategoryBreakdownItem) => item.name),
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        ...baseLabels,
                        total: {
                            ...(baseLabels?.total ?? {}),
                            label: totalLabel.value,
                        },
                    },
                },
            },
        },
    };
});
</script>

<template>
    <div v-if="!isEmpty" ref="chartContainer">
        <VueApexCharts
            type="donut"
            width="100%"
            height="300"
            :options="chartOptions"
            :series="chartSeries"
        />
    </div>
    <Empty v-else class="py-10">
        <EmptyTitle>
            <EmptyMedia class="mx-auto" variant="icon">
                <PieChart />
            </EmptyMedia>
            {{ $t('dashboard.charts.categories.empty.title') }}
        </EmptyTitle>
        <EmptyDescription>{{
            $t('dashboard.charts.categories.empty.description')
        }}</EmptyDescription>
    </Empty>
</template>
