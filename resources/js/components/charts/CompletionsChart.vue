<script setup lang="ts">
import {
    Empty,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { getBinaryTheme } from '@/composables/useAppearance';
import { prefersReducedMotion, readCssVar } from '@/lib/chart-utils';
import { MonthlyCompletionItem } from '@/types/charts';
import { trans } from 'laravel-vue-i18n';
import { TrendingUp } from 'lucide-vue-next';
import moment from 'moment';
import { computed } from 'vue';

const props = defineProps<{
    data: MonthlyCompletionItem[];
}>();

const theme = getBinaryTheme();

const chartColors = [readCssVar('--chart-1')];

const isEmpty = computed(() => props.data.every((item) => item.count === 0));

const chartSeries = [
    {
        name: trans('dashboard.charts.completions.series'),
        data: props.data.map((item) => ({
            x: moment(item.month, 'YYYY-MM').format('MMM, YYYY'),
            y: item.count,
        })),
    },
];

const chartOptions = {
    colors: chartColors,
    chart: {
        type: 'bar',
        height: 300,
        animations: {
            enabled: !prefersReducedMotion(),
        },
    },
    theme: {
        mode: theme,
    },
    dataLabels: {
        enabled: false,
    },
};
</script>

<template>
    <apexchart
        v-if="!isEmpty"
        type="bar"
        width="100%"
        height="auto"
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
