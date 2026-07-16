<script setup lang="ts">
import ProgressChart from '@/components/charts/ProgressChart.vue';
import GoalEntryFormModal from '@/components/goal_entries/GoalEntryFormModal.vue';
import GoalBadges from '@/components/goals/GoalBadges.vue';
import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import Timeline from '@/components/milestones/Timeline.vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { streakUnit as streakUnitHelper } from '@/lib/streak';
import { getDateDiffFromNow } from '@/lib/utils';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    Circle,
    Ellipsis,
    Flame,
    ListChecks,
    Pencil,
    Plus,
} from 'lucide-vue-next';
import moment from 'moment';
import { computed, ref } from 'vue';

const props = defineProps<{
    goal: Goal;
    chartEntries: { entry_date: string; value: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'goals.breadcrumb.index', href: goals.index().url },
    { title: props.goal.title, href: goals.show(props.goal.id).url },
];

const isCompleted = computed(
    () => !!props.goal.completed_at && props.goal.status === 'completed',
);
const isInProgress = computed(() => props.goal.status === 'in_progress');
const isPaused = computed(() => props.goal.status === 'paused');
const statusLabel = computed(() => `goals.statuses.${props.goal.status}`);

const MILESTONEABLE_GOAL_TYPES = ['quantifiable', 'multi_step'];
const isMilestoneable = computed(() =>
    MILESTONEABLE_GOAL_TYPES.includes(props.goal.type),
);

const doneMilestones = computed(
    () => props.goal.milestones?.filter((m) => m.is_completed).length ?? 0,
);
const totalMilestones = computed(() => props.goal.milestones?.length ?? 0);

type SummaryTile = { n: string; l: string; nCount?: number };

const deadlineLabel = computed<SummaryTile>(() => {
    if (!props.goal.deadline) return { n: '—', l: 'goals.summary.no_deadline' };
    const diff = getDateDiffFromNow(props.goal.deadline);
    if (diff < 0)
        return { n: 'goals.summary.overdue', l: 'goals.summary.past_deadline' };
    return {
        n: 'goals.summary.days',
        nCount: diff,
        l: 'goals.summary.until_deadline',
    };
});

const fmtDate = (d: string) => moment(d).format('MMM D, YYYY');

const currentStreak = computed(() => props.goal.streak?.current ?? 0);
const longestStreak = computed(() => props.goal.streak?.longest ?? 0);
const streakUnit = computed(() => streakUnitHelper(props.goal));
const isNegativeStreak = computed(() => props.goal.polarity === 'negative');
const deadlineProgress = computed(() => {
    if (!props.goal.deadline || !props.goal.start_date) return null;
    const total = moment(props.goal.deadline).diff(
        moment(props.goal.start_date),
        'days',
    );
    if (total <= 0) return null;
    const elapsed = Math.min(
        Math.max(moment().diff(moment(props.goal.start_date), 'days'), 0),
        total,
    );
    return { elapsed, total, percent: Math.round((elapsed / total) * 100) };
});

const summaryTiles = computed<SummaryTile[]>(() => {
    const status = {
        n: `goals.statuses.${props.goal.status}`,
        l: 'goals.summary.status',
    };
    const deadline = deadlineLabel.value;
    const priority = {
        n: `goals.priorities.${props.goal.priority}`,
        l: 'goals.summary.priority',
    };

    if (props.goal.type === 'quantifiable') {
        return [
            {
                n: `${Math.round(props.goal.progress_percentage)}%`,
                l: 'goals.summary.progress',
            },
            {
                n: `${props.goal.current_value} / ${props.goal.target_value}`,
                l: props.goal.unit ?? 'goals.summary.target',
            },
            deadline,
            {
                n: `${props.chartEntries.length}`,
                l: 'goals.summary.entries_logged',
            },
        ];
    }

    if (props.goal.type === 'multi_step') {
        return [
            {
                n: `${doneMilestones.value} / ${totalMilestones.value}`,
                l: 'goals.summary.steps_done',
            },
            status,
            deadline,
            priority,
        ];
    }

    if (props.goal.type === 'recurring') {
        return [
            status,
            {
                n: props.goal.recurrence
                    ? `goals.recurrences.${props.goal.recurrence}`
                    : '—',
                l: 'goals.summary.recurrence',
            },
            deadline,
            priority,
        ];
    }

    // simple
    return [
        status,
        {
            n: props.goal.start_date ? fmtDate(props.goal.start_date) : '—',
            l: 'goals.summary.started',
        },
        deadline,
        priority,
    ];
});

const recentEntries = computed(() => props.goal.entries?.slice(0, 5) ?? []);
</script>

<template>
    <Head :title="goal.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Header -->
            <div class="space-y-3">
                <GoalBadges :goal="goal" />
                <PageHeader
                    :title="goal.title"
                    :description="goal.description ?? undefined"
                >
                    <template #actions>
                        <div class="flex items-center gap-2">
                            <GoalEntryFormModal :goal v-if="
                                goal.type === 'quantifiable' && !isCompleted
                            " />

                            <Button v-else-if="!isCompleted" as-child>
                                <Link
                                    :method="goals.complete(goal).method"
                                    :href="goals.complete(goal).url"
                                >
                                    <CheckCircle2 />
                                    {{ $t('goals.actions.mark_completed') }}
                                </Link>
                            </Button>

                            <Button variant="outline" as-child>
                                <Link :href="goals.edit(goal).url">
                                    <Pencil />
                                    {{ $t('common.actions.edit') }}
                                </Link>
                            </Button>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" size="icon">
                                        <Ellipsis />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuGroup>
                                        <DropdownMenuItem
                                            v-if="
                                                goal.type === 'quantifiable' &&
                                                !isCompleted
                                            "
                                            as-child
                                        >
                                            <Link
                                                :method="
                                                    goals.complete(goal).method
                                                "
                                                :href="goals.complete(goal).url"
                                                class="w-full cursor-pointer"
                                                >{{
                                                    $t(
                                                        'goals.actions.mark_completed',
                                                    )
                                                }}</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="isInProgress"
                                            as-child
                                        >
                                            <Link
                                                :method="
                                                    goals.updateStatus(goal)
                                                        .method
                                                "
                                                :href="
                                                    goals.updateStatus(goal).url
                                                "
                                                :data="{ status: 'paused' }"
                                                class="w-full cursor-pointer"
                                                >{{
                                                    $t('goals.actions.pause')
                                                }}</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="isPaused"
                                            as-child
                                        >
                                            <Link
                                                :method="
                                                    goals.updateStatus(goal)
                                                        .method
                                                "
                                                :href="
                                                    goals.updateStatus(goal).url
                                                "
                                                :data="{
                                                    status: 'in_progress',
                                                }"
                                                class="w-full cursor-pointer"
                                                >{{
                                                    $t('goals.actions.resume')
                                                }}</Link
                                            >
                                        </DropdownMenuItem>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    class="cursor-pointer"
                                                    @select.prevent
                                                    >{{
                                                        $t(
                                                            'common.actions.delete',
                                                        )
                                                    }}</DropdownMenuItem
                                                >
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>{{
                                                        $t(
                                                            'common.confirm.title',
                                                        )
                                                    }}</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        {{
                                                            $t(
                                                                'goals.delete.description',
                                                            )
                                                        }}
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>{{
                                                        $t(
                                                            'common.actions.cancel',
                                                        )
                                                    }}</AlertDialogCancel>
                                                    <AlertDialogAction
                                                        variant="destructive"
                                                        @click="
                                                            router.delete(
                                                                goals.destroy(
                                                                    goal,
                                                                ),
                                                            )
                                                        "
                                                        >{{
                                                            $t(
                                                                'common.actions.delete',
                                                            )
                                                        }}</AlertDialogAction
                                                    >
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </DropdownMenuGroup>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </template>
                </PageHeader>
            </div>

            <!-- Summary tiles -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div
                    v-for="(tile, i) in summaryTiles"
                    :key="i"
                    class="rounded-lg bg-muted px-4 py-3"
                >
                    <p class="font-display text-xl font-semibold capitalize">
                        {{
                            tile.nCount !== undefined
                                ? $tChoice(tile.n, tile.nCount, {
                                      count: tile.nCount.toString(),
                                  })
                                : $t(tile.n)
                        }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ $t(tile.l) }}
                    </p>
                </div>
            </div>

            <!-- Two-column body -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left (2fr) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Simple: prominent done / active state -->
                    <section
                        v-if="goal.type === 'simple'"
                        class="rounded-xl border bg-card p-8 text-center"
                        :class="
                            isCompleted
                                ? 'border-success-subtle-border bg-success-subtle'
                                : ''
                        "
                    >
                        <component
                            :is="isCompleted ? CheckCircle2 : Circle"
                            class="mx-auto size-12"
                            :class="
                                isCompleted
                                    ? 'text-success'
                                    : 'text-muted-foreground'
                            "
                        />
                        <p class="mt-3 font-display text-xl font-semibold">
                            {{ $t(statusLabel) }}
                        </p>
                        <p
                            v-if="isCompleted && goal.completed_at"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{
                                $t('goals.show.simple_completed_on', {
                                    date: fmtDate(goal.completed_at),
                                })
                            }}
                        </p>
                        <p v-else class="mt-1 text-sm text-muted-foreground">
                            {{ $t('goals.show.simple_prompt') }}
                        </p>
                    </section>

                    <!-- Progress over time (quantifiable) -->
                    <section
                        v-if="goal.type === 'quantifiable'"
                        class="rounded-xl border bg-card p-4"
                    >
                        <h4 class="mb-3 font-display text-base font-semibold">
                            {{ $t('goals.show.progress_over_time') }}
                        </h4>
                        <div v-if="chartEntries && chartEntries.length > 0">
                            <ProgressChart
                                :entries="chartEntries"
                                :target-value="goal.target_value"
                                :unit="goal.unit"
                            />
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            {{ $t('goals.show.no_chart_data') }}
                        </p>
                    </section>

                    <!-- Recurring streak -->
                    <section
                        v-else-if="goal.type === 'recurring'"
                        class="rounded-xl border bg-card p-4"
                    >
                        <h4 class="mb-3 font-display text-base font-semibold">
                            {{ $t('goals.streak.title') }}
                        </h4>
                        <template v-if="isNegativeStreak">
                            <div
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <Flame
                                    class="size-5"
                                    :class="
                                        currentStreak > 0
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                />
                                {{
                                    $tChoice(
                                        `goals.streak.negative.${streakUnit}`,
                                        currentStreak,
                                        { count: currentStreak.toString() },
                                    )
                                }}
                            </div>
                            <p
                                v-if="longestStreak > 0"
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                {{ $t('goals.streak.longest_label') }}
                                <span class="font-medium text-foreground">{{
                                    $tChoice(
                                        `goals.streak.negative.${streakUnit}`,
                                        longestStreak,
                                        { count: longestStreak.toString() },
                                    )
                                }}</span>
                            </p>
                            <div
                                v-if="deadlineProgress"
                                class="mt-3 space-y-1.5"
                            >
                                <Progress
                                    :model-value="deadlineProgress.percent"
                                    class="h-1.5"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        $t('goals.streak.deadline_progress', {
                                            elapsed:
                                                deadlineProgress.elapsed.toString(),
                                            total: deadlineProgress.total.toString(),
                                        })
                                    }}
                                </p>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <Flame
                                    class="size-5"
                                    :class="
                                        currentStreak > 0
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                />
                                <template v-if="currentStreak > 0">{{
                                    $tChoice(
                                        `goals.streak.positive.${streakUnit}`,
                                        currentStreak,
                                        { count: currentStreak.toString() },
                                    )
                                }}</template>
                                <template v-else>{{
                                    $t('goals.streak.none')
                                }}</template>
                            </div>
                            <p
                                v-if="longestStreak > 0"
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                {{ $t('goals.streak.longest_label') }}
                                <span class="font-medium text-foreground">{{
                                    $tChoice(
                                        `goals.streak.positive.${streakUnit}`,
                                        longestStreak,
                                        { count: longestStreak.toString() },
                                    )
                                }}</span>
                            </p>
                        </template>
                    </section>

                    <!-- Milestones -->
                    <section
                        v-if="isMilestoneable"
                        class="rounded-xl border bg-card p-4"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-2"
                        >
                            <div>
                                <h4
                                    class="font-display text-base font-semibold"
                                >
                                    {{
                                        goal.type === 'multi_step'
                                            ? $t('goals.show.steps')
                                            : $t('goals.show.milestones')
                                    }}
                                </h4>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        $t('goals.show.milestones_progress', {
                                            done: doneMilestones.toString(),
                                            total: totalMilestones.toString(),
                                        })
                                    }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <MilestoneFormModal :goal_id="goal.id">
                                    <template #trigger>
                                        <Button variant="outline" size="sm">
                                            <Plus class="size-4" />
                                            {{ $t('common.actions.add') }}
                                        </Button>
                                    </template>
                                </MilestoneFormModal>
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="`${goals.edit(goal).url}?tab=milestones`"
                                    >
                                        <ListChecks class="size-4" />
                                        {{ $t('common.actions.manage') }}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                        <Timeline v-if="totalMilestones > 0" :record="goal" />
                        <p v-else class="text-sm text-muted-foreground">
                            {{ $t('goals.show.no_milestones') }}
                        </p>
                    </section>
                </div>

                <!-- Right (1fr) -->
                <div class="space-y-6">
                    <!-- About -->
                    <section class="rounded-xl border bg-card p-4">
                        <h4 class="mb-3 font-display text-base font-semibold">
                            {{ $t('goals.show.about') }}
                        </h4>
                        <dl class="space-y-1.5 text-sm text-muted-foreground">
                            <div v-if="goal.category?.name">
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_category')
                                }}</span>
                                · {{ goal.category.name }}
                            </div>
                            <div v-if="goal.start_date">
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_started')
                                }}</span>
                                · {{ fmtDate(goal.start_date) }}
                            </div>
                            <div v-if="goal.deadline">
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_deadline')
                                }}</span>
                                · {{ fmtDate(goal.deadline) }}
                            </div>
                            <div v-if="goal.type === 'quantifiable'">
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_direction')
                                }}</span>
                                ·
                                <span class="capitalize">{{
                                    $t(`goals.directions.${goal.direction}`)
                                }}</span>
                            </div>
                            <div v-if="goal.recurrence">
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_recurrence')
                                }}</span>
                                ·
                                <span class="capitalize">{{
                                    $t(`goals.recurrences.${goal.recurrence}`)
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-foreground">{{
                                    $t('goals.show.about_priority')
                                }}</span>
                                ·
                                <span class="capitalize">{{
                                    $t(`goals.priorities.${goal.priority}`)
                                }}</span>
                            </div>
                        </dl>
                    </section>

                    <!-- Recent entries (quantifiable) -->
                    <section
                        v-if="goal.type === 'quantifiable'"
                        class="rounded-xl border bg-card p-4"
                    >
                        <h4 class="mb-3 font-display text-base font-semibold">
                            {{ $t('goals.show.recent_entries') }}
                        </h4>
                        <div v-if="recentEntries.length > 0">
                            <div
                                v-for="entry in recentEntries"
                                :key="entry.id"
                                class="flex items-center justify-between border-b border-border/60 py-2 text-sm last:border-0"
                            >
                                <span class="text-muted-foreground">
                                    {{ fmtDate(entry.entry_date) }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="font-semibold">
                                        {{ entry.value }} {{ goal.unit }}
                                    </span>
                                    <span
                                        class="text-xs"
                                        :class="
                                            entry.increment_value >= 0
                                                ? 'text-success'
                                                : 'text-destructive'
                                        "
                                    >
                                        {{
                                            entry.increment_value >= 0
                                                ? '+'
                                                : ''
                                        }}{{ entry.increment_value }}
                                    </span>
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                class="mt-3 w-full"
                                as-child
                            >
                                <Link :href="goals.entries.get(goal).url">
                                    {{
                                        $tChoice(
                                            'goals.show.view_all',
                                            chartEntries.length,
                                            {
                                                count: chartEntries.length.toString(),
                                            },
                                        )
                                    }}
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            {{ $t('goals.show.no_entries') }}
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
