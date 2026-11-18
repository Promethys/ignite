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
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useReorderableList } from '@/composables/useReorderableList';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { CategoryBreakdownItem, MonthlyCompletionItem } from '@/types/charts';
import { Goal } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Goal as GoalIcon, GripVertical, Plus } from 'lucide-vue-next';
import { ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    pinnedGoals: Goal[];
    otherGoals: Goal[];
    activeGoalsCount: number;
    totalGoalsCount: number;
    completedGoalsCount: number;
    completionRate: number;
    monthlyCompletions: MonthlyCompletionItem[];
    categoryBreakdown: CategoryBreakdownItem[];
}>();

const orderedPins = ref<Goal[]>([...props.pinnedGoals]);
let pending = false;
let inFlight = false;
let ready = false;
let revision = 0;

watch(
    () => props.pinnedGoals,
    (pins) => {
        if (!pending) orderedPins.value = [...pins];
    },
);

const submitReorder = () => {
    if (inFlight) {
        ready = true;
        return;
    }

    inFlight = true;
    ready = false;
    const submittedRevision = revision;
    const restoreOrder = () => {
        if (revision === submittedRevision) {
            orderedPins.value = [...props.pinnedGoals];
        }
    };

    router.patch(
        goals.pins.reorder(),
        { goals: orderedPins.value.map((goal) => goal.id) },
        {
            preserveScroll: true,
            onError: restoreOrder,
            onHttpException: restoreOrder,
            onNetworkError: restoreOrder,
            onFinish: () => {
                inFlight = false;
                if (revision === submittedRevision) {
                    pending = false;
                    orderedPins.value = [...props.pinnedGoals];
                } else if (ready) {
                    submitReorder();
                }
            },
        },
    );
};

const debouncedReorder = useDebounceFn(submitReorder, 400);

const { move: moveWithKeyboard } = useReorderableList(
    useTemplateRef<HTMLElement>('pinnedList'),
    orderedPins,
    {
        keyOf: (goal) => goal.id,
        onReorder: () => {
            pending = true;
            ready = false;
            revision++;
            debouncedReorder();
        },
    },
);

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
                <CardHeader
                    class="flex flex-row items-center justify-between gap-4"
                >
                    <CardTitle class="font-display">
                        {{ $t('dashboard.your_goals') }}
                    </CardTitle>
                    <Button variant="link" class="px-0" as-child>
                        <Link :href="goals.index().url">{{
                            $t('dashboard.see_all_goals')
                        }}</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="orderedPins.length || otherGoals.length"
                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <div ref="pinnedList" class="contents">
                            <GoalCard
                                v-for="(goal, index) in orderedPins"
                                :key="goal.id"
                                :item="goal"
                            >
                                <template #header-actions>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        :data-drag-handle="goal.id"
                                        class="size-8 cursor-grab touch-none active:cursor-grabbing"
                                        :aria-label="
                                            $t('dashboard.move_pinned_goal', {
                                                title: goal.title,
                                            })
                                        "
                                        @click.stop.prevent
                                        @keydown.up.prevent="
                                            moveWithKeyboard(index, index - 1)
                                        "
                                        @keydown.down.prevent="
                                            moveWithKeyboard(index, index + 1)
                                        "
                                        @keydown.left.prevent="
                                            moveWithKeyboard(index, index - 1)
                                        "
                                        @keydown.right.prevent="
                                            moveWithKeyboard(index, index + 1)
                                        "
                                    >
                                        <GripVertical class="size-4" />
                                    </Button>
                                </template>
                            </GoalCard>
                        </div>
                        <GoalCard
                            v-for="goal in otherGoals"
                            :key="goal.id"
                            :item="goal"
                        />
                    </div>
                    <Empty v-else>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <GoalIcon />
                            </EmptyMedia>
                            <EmptyTitle>
                                {{ $t('dashboard.empty.title') }}
                            </EmptyTitle>
                            <EmptyDescription>{{
                                $t('dashboard.empty.description')
                            }}</EmptyDescription>
                        </EmptyHeader>
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
