<script setup lang="ts">
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
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getDateDiffFromNow } from '@/lib/utils';
import goals from '@/routes/goals';
import { Goal } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Ellipsis } from 'lucide-vue-next';
import moment from 'moment';
import { computed } from 'vue';
import GoalBadges from './GoalBadges.vue';

const props = defineProps<{ item: Goal }>();

const isCompleted = computed(
    () => !!props.item.completed_at && props.item.status === 'completed',
);
const isInProgress = computed(() => props.item.status === 'in_progress');
const isPaused = computed(() => props.item.status === 'paused');

const deadlineState = computed(() => {
    if (isCompleted.value || !props.item.deadline) return 'none';
    const diff = getDateDiffFromNow(props.item.deadline);
    if (diff < 0) return 'overdue';
    if (diff === 0) return 'due';
    return 'ok';
});
</script>

<template>
    <Link :href="goals.show(item.id).url" class="block h-full">
        <Card
            class="h-full gap-4 rounded-xl border px-6 py-6 text-sm shadow-sm transition-colors hover:bg-accent/40"
            :class="
                isCompleted
                    ? 'border-success-subtle-border bg-success-subtle hover:bg-success-subtle'
                    : ''
            "
        >
            <!-- Header: badges + menu -->
            <div class="flex w-full items-start justify-between gap-2">
                <GoalBadges :goal="item" />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child @click.stop.prevent>
                        <Button variant="ghost" size="icon" class="size-8">
                            <Ellipsis />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent @click.stop>
                        <DropdownMenuGroup>
                            <DropdownMenuItem v-if="!isCompleted" as-child>
                                <Link
                                    :method="goals.complete(item).method"
                                    :href="goals.complete(item).url"
                                    class="w-full cursor-pointer"
                                    >Mark as completed</Link
                                >
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="isInProgress" as-child>
                                <Link
                                    :method="goals.updateStatus(item).method"
                                    :href="goals.updateStatus(item).url"
                                    :data="{ status: 'paused' }"
                                    class="w-full cursor-pointer"
                                    >Pause</Link
                                >
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="isPaused" as-child>
                                <Link
                                    :method="goals.updateStatus(item).method"
                                    :href="goals.updateStatus(item).url"
                                    :data="{ status: 'in_progress' }"
                                    class="w-full cursor-pointer"
                                    >Resume</Link
                                >
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="goals.edit(item).url"
                                    class="w-full cursor-pointer"
                                    >Edit</Link
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
                                            This action cannot be undone. This
                                            will permanently delete your goal.
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
                                                    goals.destroy(item),
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

            <!-- Title + description (height-reserved) -->
            <div class="space-y-1">
                <h3 class="font-display text-lg font-semibold">
                    {{ item.title }}
                </h3>
                <p class="line-clamp-1 min-h-5 text-sm text-muted-foreground">
                    {{ item.description }}
                </p>
            </div>

            <!-- Type-specific progress zone -->
            <slot name="progress" />

            <!-- Footer: category + deadline -->
            <div
                class="mt-auto flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>{{ item.category?.name }}</span>
                <span
                    v-if="item.deadline"
                    :class="{
                        'font-semibold text-warning': deadlineState === 'due',
                        'font-semibold text-destructive':
                            deadlineState === 'overdue',
                    }"
                >
                    {{ moment(item.deadline).format('L') }}
                </span>
            </div>
        </Card>
    </Link>
</template>
