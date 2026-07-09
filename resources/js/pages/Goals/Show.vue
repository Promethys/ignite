<script setup lang="ts">
import ProgressChart from '@/components/charts/ProgressChart.vue';
import GoalBadges from '@/components/goals/GoalBadges.vue';
import InputError from '@/components/InputError.vue';
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
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { getDateDiffFromNow, toTitleCase } from '@/lib/utils';
import { streakUnit as streakUnitHelper } from '@/lib/streak';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    { title: 'Goals', href: goals.index().url },
    { title: props.goal.title, href: goals.show(props.goal.id).url },
];

const isCompleted = computed(
    () => !!props.goal.completed_at && props.goal.status === 'completed',
);
const isInProgress = computed(() => props.goal.status === 'in_progress');
const isPaused = computed(() => props.goal.status === 'paused');
const statusLabel = computed(() =>
    toTitleCase(props.goal.status.replace('_', ' ')),
);

const MILESTONEABLE_GOAL_TYPES = ['quantifiable', 'multi_step'];
const isMilestoneable = computed(() =>
    MILESTONEABLE_GOAL_TYPES.includes(props.goal.type),
);

const doneMilestones = computed(
    () => props.goal.milestones?.filter((m) => m.is_completed).length ?? 0,
);
const totalMilestones = computed(() => props.goal.milestones?.length ?? 0);

const deadlineLabel = computed(() => {
    if (!props.goal.deadline) return { n: '—', l: 'No deadline' };
    const diff = getDateDiffFromNow(props.goal.deadline);
    if (diff < 0) return { n: 'Overdue', l: 'Past deadline' };
    return { n: `${diff} days`, l: 'Until deadline' };
});

const fmtDate = (d: string) => moment(d).format('MMM D, YYYY');

const currentStreak = computed(() => props.goal.streak?.current ?? 0);
const longestStreak = computed(() => props.goal.streak?.longest ?? 0);
const streakUnit = computed(() => streakUnitHelper(props.goal));

const summaryTiles = computed(() => {
    const status = {
        n: props.goal.status.replace('_', ' '),
        l: 'Status',
    };
    const deadline = deadlineLabel.value;
    const priority = { n: props.goal.priority, l: 'Priority' };

    if (props.goal.type === 'quantifiable') {
        return [
            {
                n: `${Math.round(props.goal.progress_percentage)}%`,
                l: 'Progress',
            },
            {
                n: `${props.goal.current_value} / ${props.goal.target_value}`,
                l: props.goal.unit ?? 'Target',
            },
            deadline,
            { n: `${props.chartEntries.length}`, l: 'Entries logged' },
        ];
    }

    if (props.goal.type === 'multi_step') {
        return [
            {
                n: `${doneMilestones.value} / ${totalMilestones.value}`,
                l: 'Steps done',
            },
            status,
            deadline,
            priority,
        ];
    }

    if (props.goal.type === 'recurring') {
        return [
            status,
            { n: props.goal.recurrence ?? '—', l: 'Recurrence' },
            deadline,
            priority,
        ];
    }

    // simple
    return [
        status,
        {
            n: props.goal.start_date ? fmtDate(props.goal.start_date) : '—',
            l: 'Started',
        },
        deadline,
        priority,
    ];
});

const recentEntries = computed(() => props.goal.entries?.slice(0, 5) ?? []);

const logOpen = ref(false);
const entryForm = useForm({
    increment: undefined as number | undefined,
    note: '' as string,
});

const submitEntry = () => {
    entryForm.post(goals.entries.store(props.goal).url, {
        preserveScroll: true,
        onSuccess: () => {
            entryForm.reset();
            logOpen.value = false;
        },
    });
};
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
                            <Dialog
                                v-if="
                                    goal.type === 'quantifiable' && !isCompleted
                                "
                                v-model:open="logOpen"
                            >
                                <DialogTrigger as-child>
                                    <Button>
                                        <Plus />
                                        Log progress
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Log progress</DialogTitle>
                                        <DialogDescription>
                                            Add an entry to track your progress
                                            on this goal.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form
                                        id="log-progress-form"
                                        class="space-y-4"
                                        @submit.prevent="submitEntry"
                                    >
                                        <div class="space-y-2">
                                            <Label for="increment"
                                                >Progress value</Label
                                            >
                                            <div class="flex items-end gap-2">
                                                <Input
                                                    id="increment"
                                                    v-model="
                                                        entryForm.increment
                                                    "
                                                    type="number"
                                                    step="0.01"
                                                    placeholder="25"
                                                    required
                                                />
                                                <span
                                                    class="pb-2 text-sm text-muted-foreground"
                                                    >{{ goal.unit }}</span
                                                >
                                            </div>
                                            <InputError
                                                :message="
                                                    entryForm.errors.increment
                                                "
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="note"
                                                >Note (optional)</Label
                                            >
                                            <Textarea
                                                id="note"
                                                v-model="entryForm.note"
                                                placeholder="Good progress today..."
                                                rows="3"
                                            />
                                            <InputError
                                                :message="entryForm.errors.note"
                                            />
                                        </div>
                                    </form>
                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                >Cancel</Button
                                            >
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            form="log-progress-form"
                                            :disabled="entryForm.processing"
                                        >
                                            {{
                                                entryForm.processing
                                                    ? 'Logging...'
                                                    : 'Log progress'
                                            }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Button v-else-if="!isCompleted" as-child>
                                <Link
                                    :method="goals.complete(goal).method"
                                    :href="goals.complete(goal).url"
                                >
                                    <CheckCircle2 />
                                    Mark as completed
                                </Link>
                            </Button>

                            <Button variant="outline" as-child>
                                <Link :href="goals.edit(goal).url">
                                    <Pencil />
                                    Edit
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
                                                >Mark as completed</Link
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
                                                >Pause</Link
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
                                                >Resume</Link
                                            >
                                        </DropdownMenuItem>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    class="cursor-pointer"
                                                    @select.prevent
                                                    >Delete</DropdownMenuItem
                                                >
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle
                                                        >Are you absolutely
                                                        sure?</AlertDialogTitle
                                                    >
                                                    <AlertDialogDescription>
                                                        This action cannot be
                                                        undone. This will
                                                        permanently delete your
                                                        goal.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel
                                                        >Cancel</AlertDialogCancel
                                                    >
                                                    <AlertDialogAction
                                                        variant="destructive"
                                                        @click="
                                                            router.delete(
                                                                goals.destroy(
                                                                    goal,
                                                                ),
                                                            )
                                                        "
                                                        >Delete</AlertDialogAction
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
                        {{ tile.n }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ tile.l }}
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
                            {{ statusLabel }}
                        </p>
                        <p
                            v-if="isCompleted && goal.completed_at"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            Completed on {{ fmtDate(goal.completed_at) }}
                        </p>
                        <p v-else class="mt-1 text-sm text-muted-foreground">
                            Mark this goal complete when you're done.
                        </p>
                    </section>

                    <!-- Progress over time (quantifiable) -->
                    <section
                        v-if="goal.type === 'quantifiable'"
                        class="rounded-xl border bg-card p-4"
                    >
                        <h4 class="mb-3 font-display text-base font-semibold">
                            Progress over time
                        </h4>
                        <div v-if="chartEntries && chartEntries.length > 0">
                            <ProgressChart
                                :entries="chartEntries"
                                :target-value="goal.target_value"
                                :unit="goal.unit"
                            />
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No progress data yet. Log your first entry to see
                            the chart.
                        </p>
                    </section>

                    <!-- Recurring streak -->
                    <section
                        v-else-if="goal.type === 'recurring'"
                        class="rounded-xl border bg-card p-4"
                    >
                        <h4 class="mb-3 font-display text-base font-semibold">
                            Streak
                        </h4>
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
                            <template v-if="currentStreak > 0"
                                >{{ currentStreak }}-{{ streakUnit }}
                                streak</template
                            >
                            <template v-else>No active streak</template>
                        </div>
                        <p
                            v-if="longestStreak > 0"
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            Longest:
                            <span class="font-medium text-foreground"
                                >{{ longestStreak }}-{{ streakUnit }}
                                streak</span
                            >
                        </p>
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
                                            ? 'Steps'
                                            : 'Milestones'
                                    }}
                                </h4>
                                <p class="text-xs text-muted-foreground">
                                    {{ doneMilestones }} of
                                    {{ totalMilestones }} completed
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <MilestoneFormModal :goal_id="goal.id">
                                    <template #trigger>
                                        <Button variant="outline" size="sm">
                                            <Plus class="size-4" />
                                            Add
                                        </Button>
                                    </template>
                                </MilestoneFormModal>
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="`${goals.edit(goal).url}?tab=milestones`"
                                    >
                                        <ListChecks class="size-4" />
                                        Manage
                                    </Link>
                                </Button>
                            </div>
                        </div>
                        <Timeline v-if="totalMilestones > 0" :record="goal" />
                        <p v-else class="text-sm text-muted-foreground">
                            No milestones yet. Add checkpoints to break this
                            goal into steps.
                        </p>
                    </section>
                </div>

                <!-- Right (1fr) -->
                <div class="space-y-6">
                    <!-- About -->
                    <section class="rounded-xl border bg-card p-4">
                        <h4 class="mb-3 font-display text-base font-semibold">
                            About
                        </h4>
                        <dl class="space-y-1.5 text-sm text-muted-foreground">
                            <div v-if="goal.category?.name">
                                <span class="font-medium text-foreground"
                                    >Category</span
                                >
                                · {{ goal.category.name }}
                            </div>
                            <div v-if="goal.start_date">
                                <span class="font-medium text-foreground"
                                    >Started</span
                                >
                                · {{ fmtDate(goal.start_date) }}
                            </div>
                            <div v-if="goal.deadline">
                                <span class="font-medium text-foreground"
                                    >Deadline</span
                                >
                                · {{ fmtDate(goal.deadline) }}
                            </div>
                            <div v-if="goal.type === 'quantifiable'">
                                <span class="font-medium text-foreground"
                                    >Direction</span
                                >
                                ·
                                <span class="capitalize">{{
                                    goal.direction
                                }}</span>
                            </div>
                            <div v-if="goal.recurrence">
                                <span class="font-medium text-foreground"
                                    >Recurrence</span
                                >
                                ·
                                <span class="capitalize">{{
                                    goal.recurrence
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-foreground"
                                    >Priority</span
                                >
                                ·
                                <span class="capitalize">{{
                                    goal.priority
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
                            Recent entries
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
                                    View all {{ chartEntries.length }}
                                    {{
                                        chartEntries.length === 1
                                            ? 'entry'
                                            : 'entries'
                                    }}
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No entries yet. Log your first entry to get started.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
