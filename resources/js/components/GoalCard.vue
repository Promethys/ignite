<script setup lang="ts">
import { Goal } from '@/types/models';
import TextLink from './TextLink.vue';
import { router } from '@inertiajs/vue3';
import goals from '@/routes/goals';
import { getDateDiffFromNow, toTitleCase } from '@/lib/utils';
import Progress from './ui/progress/Progress.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import CardFooter from './ui/card/CardFooter.vue';
import moment from 'moment';

defineProps<{
    item: Goal;
}>();

const getStatusDisplayName = function (name: string) {
    return toTitleCase(name.replace('_', ' '));
}
</script>

<template>
    <Card class="p-2 rounded-lg gap-2 text-sm">
        <CardHeader class="p-2">
            <CardTitle class="flex justify-between gap-2 text-sm leading-6">
                <div>
                    {{ item.icon }} {{ item.title }}
                </div>
                <div class="space-x-2">
                    <TextLink :href="goals.edit(item).url" class="font-thin">Edit</TextLink>
                    <AlertDialog>
                        <AlertDialogTrigger class="font-thin text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out cursor-pointer hover:decoration-current! dark:decoration-neutral-500">Delete</AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                <AlertDialogDescription>
                                This action cannot be undone. This will permanently delete your goal.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction variant="destructive" @click="router.delete(goals.destroy(item))">
                                    Delete
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </CardTitle>
            <CardDescription>
                <span v-if="item.category?.name">{{ item.category?.name }} • </span>
                <span>
                    {{ getStatusDisplayName(item.status) }}
                </span>
                <span>
                    •
                    {{ toTitleCase(item.priority) + ' Priority' }}
                </span>
            </CardDescription>
        </CardHeader>
        <CardContent>
            <!-- Quantifiable: Progress bar -->
            <template v-if="item.type === 'quantifiable' && item.target_value">
                <Progress :model-value="(item.current_value / item.target_value) * 100" />
                <p>
                    {{ item.current_value }} / {{ item.target_value }} {{ item.unit }}
                </p>
            </template>

            <!-- Simple: Status only (no extra visual) -->
            <template v-else-if="item.type === 'simple'">
                <p class="text-sm text-muted">
                    {{ item.status === 'completed' ? '✓ Completed' : 'Not completed yet' }}
                </p>
            </template>

            <!-- Recurring: Streak indicator -->
            <template v-else-if="item.type === 'recurring'">
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
            <template v-else-if="item.type === 'multi_step'">
                <div class="milestones">
                    <p class="text-sm font-medium">
                        <!-- {{ completedMilestones }}/{{ totalMilestones }} milestones -->
                        {{ 0 }}/{{ 0 }} milestones
                    </p>
                    <div class="milestone-list">
                        <div v-for="milestone in item.milestones" :key="milestone.id">
                            <span>{{ milestone.is_completed ? '✅' : '☐' }}</span>
                            <span>{{ milestone.title }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </CardContent>
        <CardFooter v-if="item.deadline">
            Deadline: {{ moment(item.deadline).format('MMM DD, YYYY') }} ({{ getDateDiffFromNow(item.deadline) }} days)
        </CardFooter>
    </Card>
</template>
