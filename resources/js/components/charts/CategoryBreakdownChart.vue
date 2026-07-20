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
import { wTrans } from 'laravel-vue-i18n';
import { PieChart } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    data: CategoryBreakdownItem[];
}>();

const isEmpty = computed(() => props.data.length === 0);

const chartSeries = computed(() => props.data.map((item) => item.count));

const totalLabel = wTrans('dashboard.charts.categories.total');

const chartOptions = useChartTheme(() => {
    const base = donutChartOptions();
    const baseLabels = (base.plotOptions as Record<string, any>).pie.donut
        .labels as object;

    return {
        ...base,
        colors: props.data.map((item) => item.color),
        labels: props.data.map((item) => item.name),
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        ...baseLabels,
                        total: {
                            ...((baseLabels as Record<string, any>)
                                .total as object),
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
