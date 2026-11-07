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
import VueApexCharts from 'vue3-apexcharts';

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

const highestCount = computed(() =>
    Math.max(1, ...props.data.map((item) => item.count)),
);

const chartOptions = useChartTheme(() => ({
    ...barChartOptions(),
    yaxis: {
        ...(barChartOptions().yaxis as object),
        min: 0,
        max: highestCount.value <= 5 ? highestCount.value : undefined,
        tickAmount: Math.min(highestCount.value, 5),
        forceNiceScale: highestCount.value > 5,
        decimalsInFloat: 0,
    },
    xaxis: {
        ...(barChartOptions().xaxis as object),
        categories: formatMonthLabels(props.data.map((item) => item.month)),
    },
}));
</script>

<template>
    <VueApexCharts
        v-if="!isEmpty"
        type="bar"
        width="100%"
        height="300"
        :options="chartOptions"
        :series="chartSeries"
    />
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
