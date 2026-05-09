<script setup lang="ts">
import ProgressChart from '@/components/charts/ProgressChart.vue';
import GoalBadges from '@/components/goals/GoalBadges.vue';
import InputError from '@/components/InputError.vue';
import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import Timeline from '@/components/milestones/Timeline.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import DialogClose from '@/components/ui/dialog/DialogClose.vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Progress } from '@/components/ui/progress';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { getMilestoneViewOptions } from '@/lib/form-options';
import { getDateDiffFromNow } from '@/lib/utils';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CheckCircle2,
    ClockFading,
    ListChecks,
    Pencil,
    Plus,
    Settings2,
} from 'lucide-vue-next';
import moment from 'moment';
import { ref } from 'vue';

const props = defineProps<{
    goal: Goal;
    chartEntries: [];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
    {
        title: `"${props.goal.title}"`,
        href: '',
    },
];

const isMilestoneSwitcherOpen = ref<boolean>(false);
const viewStyle = ref(localStorage.getItem('view_style') ?? 'timeline');

const entryForm = useForm({
    increment: undefined as number | undefined,
    note: '' as string,
});

const submitEntry = () => {
    entryForm.post(goals.entries.store(props.goal).url, {
        preserveScroll: true,
        onSuccess: () => {
            entryForm.reset();
        },
    });
};

const setViewStyle = (value: string) => {
    const ALLOWED_VIEWS = ['timeline', 'checklist', 'cards', 'track'];

    const newValue = ALLOWED_VIEWS.indexOf(value) > -1 ? value : 'timeline';

    viewStyle.value = newValue;
    localStorage.setItem('view_style', newValue);
};

const MILESTONEABLE_GOAL_TYPES = ['quantifiable', 'multi_step'];
</script>

<template>
    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <div class="flex flex-row items-center justify-between">
                <h3 class="text-xl font-medium">
                    {{ goal.icon ?? '' }}
                    {{ goal.title }}
                </h3>
                <div class="flex flex-row items-center justify-between gap-2">
                    <Button
                        v-if="!goal.completed_at && goal.status !== 'completed'"
                    >
                        <CheckCircle2 />
                        <Link
                            :method="goals.complete(goal).method"
                            :href="goals.complete(goal).url"
                        >
                            Mark as completed
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="goals.edit(goal).url">
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                </div>
            </div>
            <GoalBadges :goal />
            <section class="space-y-2 text-sm">
                <h3 class="text-xl font-medium">Progress Overview</h3>
                <div>
                    <!-- Quantifiable: Progress bar -->
                    <template
                        v-if="goal.type === 'quantifiable' && goal.target_value"
                    >
                        <div class="flex flex-row items-center gap-2">
                            <Progress :model-value="goal.progress_percentage" />
                            <span class="shrink-0 text-sm">
                                <span class="font-semibold">
                                    {{ Math.round(goal.progress_percentage) }}%
                                </span>
                                <span>
                                    ({{ goal.current_value }}
                                    {{
                                        goal.direction === 'ascending'
                                            ? '/'
                                            : '→'
                                    }}
                                    {{ goal.target_value }} {{ goal.unit }})
                                </span>
                            </span>
                        </div>
                    </template>

                    <!-- Simple: Status only (no extra visual) -->
                    <template v-else-if="goal.type === 'simple'">
                        <p class="text-sm text-muted">
                            {{
                                goal.status === 'completed'
                                    ? '✓ Completed'
                                    : 'Not completed yet'
                            }}
                        </p>
                    </template>

                    <!-- Recurring: Streak indicator -->
                    <template v-else-if="goal.type === 'recurring'">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔥</span>
                            <div>
                                <!-- <p class="font-bold">{{ streakDays }}-day streak</p>
                        <p class="text-sm">{{ completionCount }} times completed</p> -->
                                <p class="font-bold">{{ 0 }}-day streak</p>
                                <p class="text-sm">{{ 0 }} times completed</p>
                            </div>
                        </div>
                    </template>

                    <!-- Multi-step: Milestone checklist -->
                    <template v-else-if="goal.type === 'multi_step'">
                        <div class="milestones">
                            <p class="text-sm font-medium">
                                <!-- {{ completedMilestones }}/{{ totalMilestones }} milestones -->
                                {{ 0 }}/{{ 0 }} milestones
                            </p>
                            <div class="milestone-list">
                                <div
                                    v-for="milestone in goal.milestones"
                                    :key="milestone.id"
                                >
                                    <span>{{
                                        milestone.is_completed ? '✅' : '☐'
                                    }}</span>
                                    <span>{{ milestone.title }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div v-if="goal.start_date || goal.deadline" class="space-x-2">
                    <span v-if="goal.start_date">
                        Started :
                        {{ moment(goal.start_date).format('MMM DD, YYYY') }}
                    </span>
                    <span v-if="goal.deadline">
                        Deadline:
                        {{ moment(goal.deadline).format('MMM DD, YYYY') }} ({{
                            getDateDiffFromNow(goal.deadline)
                        }}
                        days)
                    </span>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                <div>
                    <h3 class="mb-2 text-xl font-medium">Progress Chart</h3>
                    <div v-if="chartEntries && chartEntries.length > 0">
                        <ProgressChart
                            :entries="chartEntries"
                            :target-value="goal.target_value"
                            :unit="goal.unit"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No progress data yet. Log your first entry to see the
                        chart!
                    </p>
                </div>

                <div>
                    <h3 class="mb-2 text-xl font-medium">Stats</h3>
                </div>
            </section>

            <section v-if="MILESTONEABLE_GOAL_TYPES.includes(goal.type)">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col gap-1">
                                <CardTitle class="text-lg"
                                    >Milestones</CardTitle
                                >
                                <CardDescription>
                                    Track key checkpoints on your journey to
                                    completing this goal
                                </CardDescription>
                            </div>
                            <div class="space-x-1">
                                <MilestoneFormModal :goal>
                                    <template #trigger>
                                        <Button variant="outline" size="sm">
                                            <Plus class="size-4" />
                                            Add Milestone
                                        </Button>
                                    </template>
                                </MilestoneFormModal>
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="`${goals.edit(goal).url}?tab=milestones`"
                                    >
                                        <ListChecks class="size-4" />
                                        Manage Milestones
                                    </Link>
                                </Button>
                                <Popover v-model:open="isMilestoneSwitcherOpen">
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            class="size-8 text-muted-foreground hover:text-foreground"
                                        >
                                            <Settings2 class="size-4" />
                                            <span class="sr-only"
                                                >Display settings</span
                                            >
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent align="end" class="w-64">
                                        <div class="flex flex-col gap-4">
                                            <div class="flex flex-col gap-1">
                                                <h4 class="text-sm font-medium">
                                                    Display Style
                                                </h4>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Choose how milestones are
                                                    displayed
                                                </p>
                                            </div>
                                            <RadioGroup
                                                v-model="viewStyle"
                                                @update:model-value="
                                                    (value) => {
                                                        setViewStyle(
                                                            value?.toString() ??
                                                                'timeline',
                                                        );
                                                        isMilestoneSwitcherOpen = false;
                                                    }
                                                "
                                                class="gap-2"
                                            >
                                                <Label
                                                    v-for="option in getMilestoneViewOptions()"
                                                    :key="option.value"
                                                    class="flex items-start gap-3 rounded-lg border border-border/50 p-3 transition-colors has-[[data-state=checked]]:border-primary/50 has-[[data-state=checked]]:bg-muted/30"
                                                    :class="{
                                                        'cursor-pointer hover:bg-muted/50':
                                                            option.value ===
                                                            'timeline',
                                                    }"
                                                >
                                                    <RadioGroupItem
                                                        :id="option.value"
                                                        :value="option.value"
                                                        :disabled="
                                                            option.value !==
                                                            'timeline'
                                                        "
                                                        class="mt-0.5"
                                                    />
                                                    <div
                                                        class="flex flex-1 flex-col gap-0.5"
                                                    >
                                                        <span
                                                            class="flex items-center gap-2 text-sm font-medium"
                                                        >
                                                            {{ option.label }}
                                                            <Check
                                                                v-if="
                                                                    viewStyle ===
                                                                    option.value
                                                                "
                                                                class="size-3 text-primary"
                                                            />
                                                            <HelpTooltip
                                                                v-if="
                                                                    option.value !==
                                                                    'timeline'
                                                                "
                                                            >
                                                                <template
                                                                    #trigger
                                                                >
                                                                    <Badge
                                                                        class="text-2xs text-warning"
                                                                        :variant="
                                                                            null
                                                                        "
                                                                    >
                                                                        <ClockFading
                                                                            class="size-2"
                                                                        />
                                                                    </Badge>
                                                                </template>

                                                                Available Soon
                                                            </HelpTooltip>
                                                        </span>
                                                        <span
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                option.description
                                                            }}
                                                        </span>
                                                    </div>
                                                </Label>
                                            </RadioGroup>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Timeline :record="goal" />
                    </CardContent>
                </Card>
            </section>

            <section v-if="goal.type === 'quantifiable'" class="space-y-4">
                <h3 class="mb-2 text-xl font-medium">Log Progress</h3>

                <form @submit.prevent="submitEntry" class="max-w-md space-y-4">
                    <!-- Increment Input -->
                    <div class="space-y-2">
                        <Label for="increment">Progress Value</Label>
                        <div class="flex items-end gap-2">
                            <Input
                                id="increment"
                                v-model="entryForm.increment"
                                type="number"
                                step="0.01"
                                placeholder="25"
                                required
                            />
                            <span class="pb-2 text-sm text-muted">{{
                                goal.unit
                            }}</span>
                        </div>
                        <InputError :message="entryForm.errors.increment" />
                    </div>

                    <!-- Note Input -->
                    <div class="space-y-2">
                        <Label for="note">Note (optional)</Label>
                        <Textarea
                            id="note"
                            v-model="entryForm.note"
                            placeholder="Good progress today..."
                            rows="3"
                        />
                        <InputError :message="entryForm.errors.note" />
                    </div>

                    <!-- Submit Button -->
                    <Button type="submit" :disabled="entryForm.processing">
                        {{
                            entryForm.processing ? 'Logging...' : 'Log Progress'
                        }}
                    </Button>
                </form>

                <!-- Entry History -->
                <div
                    v-if="goal.entries && goal.entries.length > 0"
                    class="space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-medium">Progress History</h4>
                        <Button as-child>
                            <Link :href="goals.entries.get(goal).url">
                                All entries
                            </Link>
                        </Button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="entry in goal.entries"
                            :key="entry.id"
                            class="space-y-2 rounded-lg border p-4"
                        >
                            <!-- Entry Header: Date and Value -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium">
                                        {{
                                            moment(entry.entry_date).format(
                                                'MMM DD, YYYY',
                                            )
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            (entry.increment_value > 0
                                                ? '+'
                                                : '') + entry.increment_value
                                        }}
                                        {{ goal.unit }}
                                        <span class="text-xs">
                                            ({{ entry.previous_value }} →
                                            {{ entry.value }})
                                        </span>
                                    </p>
                                </div>
                                <!-- Delete button -->
                                <div class="max-w-md space-y-4">
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button variant="destructive">
                                                Delete
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent class="sm:max-w-[425px]">
                                            <DialogHeader>
                                                <DialogTitle
                                                    >Delete entry</DialogTitle
                                                >
                                                <DialogDescription>
                                                    Delete that entry from
                                                    progress history?
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    variant="destructive"
                                                    @click="
                                                        router.delete(
                                                            goals.entries.destroy(
                                                                {
                                                                    goal,
                                                                    goalEntry:
                                                                        entry.id,
                                                                },
                                                            ),
                                                        )
                                                    "
                                                >
                                                    Delete
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>

                            <!-- Entry Note (if exists) -->
                            <p v-if="entry.note" class="text-sm">
                                {{ entry.note }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <p v-else class="text-sm text-muted-foreground">
                    No progress entries yet. Log your first entry above!
                </p>
            </section>
        </div>
    </AppLayout>
</template>
