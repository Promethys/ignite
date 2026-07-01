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
import { Card } from '@/components/ui/card';
import { getDateDiffFromNow } from '@/lib/utils';
import goals from '@/routes/goals';
import { Goal } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Ellipsis } from 'lucide-vue-next';
import moment from 'moment';
import { computed } from 'vue';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import GoalBadges from './GoalBadges.vue';

const props = defineProps<{
    item: Goal;
}>();

const isCompleted = computed(() => {
    return props.item.completed_at && props.item.status === 'completed';
});
const isInProgress = computed(() => {
    return props.item.status === 'in_progress';
});
const isPaused = computed(() => {
    return props.item.status === 'paused';
});
</script>

<template>
    <Link :href="goals.show(item.id).url">
        <Card
            class="h-full cursor-pointer gap-2 rounded-4xl border-2 border-accent px-6 py-8 text-sm transition-all hover:bg-accent/50"
            :class="{ 'opacity-60': isCompleted }"
        >
            <div class="flex w-full items-center justify-between">
                <div>
                    <GoalBadges :goal="item" />
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon">
                            <Ellipsis />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuGroup>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="goals.edit(item).url"
                                    class="cursor-pointer"
                                    >Edit</Link
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
                            <DropdownMenuItem variant="destructive" as-child>
                                <AlertDialog>
                                    <AlertDialogTrigger>
                                        Delete
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle
                                                >Are you absolutely
                                                sure?</AlertDialogTitle
                                            >
                                            <AlertDialogDescription>
                                                This action cannot be undone.
                                                This will permanently delete
                                                your goal.
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
                                            >
                                                Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div class="space-y-4">
                <div class="space-y-2">
                    <h3
                        class="text-xl font-medium"
                        :class="{ 'line-through': isCompleted }"
                    >
                        {{ item.title }}
                    </h3>
                    <p
                        class="line-clamp-2 text-sm font-light"
                        v-if="item.description"
                    >
                        {{ item.description }}
                    </p>
                </div>
                <!-- <div>
                    Details here: progress bar and others
                </div> -->
                <div class="flex items-center justify-between">
                    <span v-if="item.start_date">
                        Started at: {{ moment(item.start_date).format('L') }}
                    </span>
                    <span v-if="item.deadline">
                        Deadline:
                        <span
                            :class="{
                                'font-semibold text-warning':
                                    !isCompleted &&
                                    getDateDiffFromNow(item.deadline) === 0,
                                'font-semibold text-destructive':
                                    !isCompleted &&
                                    getDateDiffFromNow(item.deadline) < 0,
                            }"
                        >
                            {{ moment(item.deadline).format('L') }}
                        </span>
                    </span>
                </div>
            </div>
        </Card>
    </Link>
</template>
