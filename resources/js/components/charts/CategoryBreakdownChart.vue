<script setup lang="ts">
import {
    Empty,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { getBinaryTheme } from '@/composables/useAppearance';
import { CategoryBreakdownItem } from '@/types/charts';
import { trans } from 'laravel-vue-i18n';
import { PieChart } from 'lucide-vue-next';
import { computed } from 'vue';
import { prefersReducedMotion } from './chart-utils';

const props = defineProps<{
    data: CategoryBreakdownItem[];
}>();

const theme = getBinaryTheme();

const isEmpty = computed(() => props.data.length === 0);

const chartColors = props.data.map((item) => item.color);

const chartSeries = props.data.map((item) => item.count);

const chartOptions = {
    colors: chartColors,
    chart: {
        type: 'donut',
        animations: {
            enabled: !prefersReducedMotion(),
        },
    },
    plotOptions: {
        pie: {
            donut: {
                labels: {
                    show: true,
                    total: {
                        showAlways: true,
                        show: true,
                        label: trans('dashboard.charts.categories.total'),
                    },
                },
            },
        },
    },
    theme: {
        mode: theme,
    },
    labels: props.data.map((item) => item.name),
};
</script>

<template>
    <apexchart
        v-if="!isEmpty"
        type="donut"
        width="100%"
        height="300"
        :options="chartOptions"
        :series="chartSeries"
    ></apexchart>
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
