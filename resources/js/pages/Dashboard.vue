<script setup lang="ts">
import GoalCard from '@/components/goals/GoalCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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
import { Goal } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Goal as GoalIcon, Plus } from 'lucide-vue-next';

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
    { label: 'Active', value: props.activeGoalsCount },
    { label: 'Completed', value: props.completedGoalsCount },
    { label: 'Completion', value: `${props.completionRate}%` },
    { label: 'Total', value: props.totalGoalsCount },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <PageHeader
                title="Welcome back!"
                description="Your momentum at a glance."
            >
                <template #actions>
                    <Button as-child class="w-full sm:w-auto">
                        <Link :href="goals.create().url">
                            <Plus />
                            New goal
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
                        {{ item.label }}
                    </p>
                </div>
            </div>

            <!-- Active goals -->
            <section class="space-y-3">
                <h2 class="font-display text-base font-semibold">
                    Active goals
                </h2>

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
                        No active goal
                    </EmptyTitle>
                    <EmptyDescription>It's cold up here...</EmptyDescription>
                    <EmptyContent>
                        <Button as-child>
                            <Link :href="goals.create().url">
                                <Plus />
                                New goal
                            </Link>
                        </Button>
                    </EmptyContent>
                </Empty>
            </section>
        </div>
    </AppLayout>
</template>
