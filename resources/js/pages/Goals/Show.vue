<script setup lang="ts">
import GoalBadges from '@/components/goals/GoalBadges.vue';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { getDateDiffFromNow } from '@/lib/utils';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import moment from 'moment';
import InputError from '@/components/InputError.vue';
import DialogClose from '@/components/ui/dialog/DialogClose.vue';
import ProgressChart from '@/components/charts/ProgressChart.vue';
import { CheckCircle2, Pencil } from 'lucide-vue-next';

const props = defineProps<{
    goal: Goal;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
    {
        title: `View "${props.goal.title}"`,
        href: '',
    },
];

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
</script>

<template>

    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div class="flex flex-row items-center justify-between">
                <h3 class="font-medium text-xl">
                    {{ goal.icon ?? '' }}
                    {{ goal.title }}
                </h3>
                <div class="flex flex-row items-center justify-between gap-2">
                    <Button v-if="!goal.completed_at && goal.status !== 'completed'">
                        <CheckCircle2 />
                        <Link :method="goals.complete(goal).method" :href="goals.complete(goal).url">
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
            <section class="text-sm space-y-2">
                <h3 class="font-medium text-xl">
                    Progress Overview
                </h3>
                <div>
                    <!-- Quantifiable: Progress bar -->
                    <template v-if="goal.type === 'quantifiable' && goal.target_value">
                        <div class="flex flex-row gap-2 items-center">
                            <Progress :model-value="goal.progress_percentage" />
                            <span class="text-sm shrink-0">
                                <span class="font-semibold">
                                    {{ Math.round(goal.progress_percentage) }}%
                                </span>
                                <span>
                                    ({{ goal.current_value }} 
                                    {{ goal.direction === 'ascending' ? '/' : '→' }} 
                                    {{ goal.target_value }} {{ goal.unit }})
                                </span>
                            </span>
                        </div>
                    </template>

                    <!-- Simple: Status only (no extra visual) -->
                    <template v-else-if="goal.type === 'simple'">
                        <p class="text-sm text-muted">
                            {{ goal.status === 'completed' ? '✓ Completed' : 'Not completed yet' }}
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
                                <div v-for="milestone in goal.milestones" :key="milestone.id">
                                    <span>{{ milestone.is_completed ? '✅' : '☐' }}</span>
                                    <span>{{ milestone.title }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div v-if="goal.start_date || goal.deadline" class="space-x-2">
                    <span v-if="goal.start_date">
                        Started : {{ moment(goal.start_date).format('MMM DD, YYYY') }}
                    </span>
                    <span v-if="goal.deadline">
                        Deadline: {{ moment(goal.deadline).format('MMM DD, YYYY') }} ({{
                            getDateDiffFromNow(goal.deadline) }} days)
                    </span>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                <div>
                    <h3 class="font-medium text-xl mb-2">
                        Progress Chart
                    </h3>
                    <div v-if="goal.entries && goal.entries.length > 0">
                        <ProgressChart 
                            :entries="goal.entries" 
                            :target-value="goal.target_value" 
                            :unit="goal.unit"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                    No progress data yet. Log your first entry to see the chart!
                    </p>
                </div>

                <div>
                    <h3 class="font-medium text-xl mb-2">
                        Stats
                    </h3>
                </div>
            </section>

            <section v-if="goal.type === 'quantifiable'" class="space-y-4">
                <h3 class="font-medium text-xl mb-2">Log Progress</h3>

                <form @submit.prevent="submitEntry" class="space-y-4 max-w-md">
                    <!-- Increment Input -->
                    <div class="space-y-2">
                        <Label for="increment">Progress Value</Label>
                        <div class="flex items-end gap-2">
                            <Input id="increment" v-model="entryForm.increment" type="number" step="0.01"
                                placeholder="25" required />
                            <span class="text-sm text-muted pb-2">{{ goal.unit }}</span>
                        </div>
                        <InputError :message="entryForm.errors.increment" />
                    </div>

                    <!-- Note Input -->
                    <div class="space-y-2">
                        <Label for="note">Note (optional)</Label>
                        <Textarea id="note" v-model="entryForm.note" placeholder="Good progress today..." rows="3" />
                        <InputError :message="entryForm.errors.note" />
                    </div>

                    <!-- Submit Button -->
                    <Button type="submit" :disabled="entryForm.processing">
                        {{ entryForm.processing ? 'Logging...' : 'Log Progress' }}
                    </Button>
                </form>

                <!-- Entry History -->
                <div v-if="goal.entries && goal.entries.length > 0" class="space-y-4">
                    <h4 class="font-medium text-lg">Progress History</h4>

                    <div class="space-y-3">
                        <div v-for="entry in goal.entries" :key="entry.id" class="border rounded-lg p-4 space-y-2">
                            <!-- Entry Header: Date and Value -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">
                                        {{ moment(entry.entry_date).format('MMM DD, YYYY') }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ (entry.increment_value > 0 ? '+' : '') + entry.increment_value }} {{
                                            goal.unit }}
                                        <span class="text-xs">
                                            ({{ entry.previous_value }} → {{ entry.value }})
                                        </span>
                                    </p>
                                </div>
                                <!-- Delete button -->
                                <div class="space-y-4 max-w-md">
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button variant="destructive">
                                                Delete
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent class="sm:max-w-[425px]">
                                            <DialogHeader>
                                                <DialogTitle>Delete entry</DialogTitle>
                                                <DialogDescription>
                                                    Delete that entry from progress history?
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button type="button" variant="secondary">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button variant="destructive" @click="router.delete(goals.entries.destroy({ goal, goalEntry: entry.id }))">
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
