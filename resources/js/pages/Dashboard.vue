<script setup lang="ts">
import CategoryBreakdownChart from '@/components/charts/CategoryBreakdownChart.vue';
import CompletionsChart from '@/components/charts/CompletionsChart.vue';
import GoalCard from '@/components/goals/GoalCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { CategoryBreakdownItem, MonthlyCompletionItem } from '@/types/charts';
import { Goal } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Goal as GoalIcon, Plus } from 'lucide-vue-next';

const props = defineProps<{
    activeGoalsList: Goal[];
    activeGoalsCount: number;
    totalGoalsCount: number;
    completedGoalsCount: number;
    completionRate: number;
    monthlyCompletions: MonthlyCompletionItem[];
    categoryBreakdown: CategoryBreakdownItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'dashboard.breadcrumb',
        href: dashboard().url,
    },
];

const stats = [
    { label: 'dashboard.stats.active', value: props.activeGoalsCount },
    { label: 'dashboard.stats.completed', value: props.completedGoalsCount },
    { label: 'dashboard.stats.completion', value: `${props.completionRate}%` },
    { label: 'dashboard.stats.total', value: props.totalGoalsCount },
];
</script>

<template>
    <Head :title="$t('dashboard.head')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <PageHeader
                :title="$t('dashboard.title')"
                :description="$t('dashboard.subtitle')"
            >
                <template #actions>
                    <Button as-child class="w-full sm:w-auto">
                        <Link :href="goals.create().url">
                            <Plus />
                            {{ $t('common.actions.new_goal') }}
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <!-- Stat band -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div
                    v-for="item in stats"
                    :key="item.label"
                    class="rounded-lg bg-muted px-4 py-3"
                >
                    <p class="font-display text-2xl font-semibold">
                        {{ item.value }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ $t(item.label) }}
                    </p>
                </div>
            </div>

            <!-- Active goals -->
            <Card>
                <CardHeader>
                    <CardTitle class="font-display">
                        {{ $t('dashboard.active_goals') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="activeGoalsList.length > 0"
                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <GoalCard
                            v-for="goal in activeGoalsList"
                            :key="goal.id"
                            :item="goal"
                        />
                    </div>
                    <Empty v-else>
                        <EmptyTitle>
                            <EmptyMedia class="mx-auto" variant="icon">
                                <GoalIcon />
                            </EmptyMedia>
                            {{ $t('dashboard.empty.title') }}
                        </EmptyTitle>
                        <EmptyDescription>{{
                            $t('dashboard.empty.description')
                        }}</EmptyDescription>
                        <EmptyContent>
                            <Button as-child>
                                <Link :href="goals.create().url">
                                    <Plus />
                                    {{ $t('common.actions.new_goal') }}
                                </Link>
                            </Button>
                        </EmptyContent>
                    </Empty>
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="font-display">
                            {{ $t('dashboard.charts.completions.title') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <CompletionsChart :data="monthlyCompletions" />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="font-display">
                            {{ $t('dashboard.charts.categories.title') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <CategoryBreakdownChart :data="categoryBreakdown" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
