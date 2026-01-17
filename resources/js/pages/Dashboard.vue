<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Goal } from '@/types/models';
import goals from '@/routes/goals';
import GoalCard from '@/components/goals/GoalCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Goal as GoalIcon, Plus } from 'lucide-vue-next';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

const props = defineProps<{
    activeGoalsList: Array<Goal>;
    activeGoalsCount: number;
    totalGoalsCount: number;
    completedGoalsCount: number;
    completionRate: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const stats = [
    { label: "Active Goals", value: props.activeGoalsCount },
    { label: "Total Goals", value: props.totalGoalsCount },
    { label: "Completed", value: props.completedGoalsCount },
    { label: "Completion Rate", value: `${props.completionRate}%` },
];
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div>
                <h1 class="text-balance text-3xl font-bold tracking-tight md:text-4xl">Welcome back!</h1>
                <p class="mt-2 text-pretty text-muted-foreground">
                    Here's your progress overview. Keep the momentum going!
                </p>
            </div>
            <!-- Stat Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="item in stats" :key="item.label">
                    <CardContent class="flex items-center gap-4 p-6">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ item.label }}</p>
                            <p class="text-2xl font-bold">{{ item.value }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Active goals Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Active Goals</h2>
                        <p class="text-sm text-muted-foreground">Track your current goals and progress</p>
                    </div>
                    <Button as-child v-if="activeGoalsList.length > 0">
                        <Link :href="goals.create().url">
                            <Plus class="mr-2 h-4 w-4" />
                            New Goal
                        </Link>
                    </Button>
                </div>

                <div v-if="activeGoalsList.length > 0" class="grid gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                    <GoalCard v-for="goal in activeGoalsList" :key="goal.id" :item="goal" />
                </div>
                <div v-else>
                    <Empty>
                        <EmptyTitle>
                            <EmptyMedia class="mx-auto" variant="icon">
                                <GoalIcon />
                            </EmptyMedia>
                            No active goal
                        </EmptyTitle>
                        <EmptyDescription>It's cold up here...</EmptyDescription>
                        <EmptyContent>
                            <Button as-child>
                                <Link :href="goals.create().url">
                                    <Plus class="mr-2 h-4 w-4" />
                                    New Goal
                                </Link>
                            </Button>
                        </EmptyContent>
                    </Empty>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
