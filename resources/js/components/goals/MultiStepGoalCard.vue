<script setup lang="ts">
import { Goal } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import goals from '@/routes/goals';
import {
    Card,
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
import { Button } from '../ui/button';
import { Ellipsis } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '../ui/dropdown-menu';
import GoalBadges from './GoalBadges.vue';
import moment from 'moment';
import { getDateDiffFromNow } from '@/lib/utils';

const props = defineProps<{
    item: Goal;
}>();

const isCompleted = props.item.completed_at && (props.item.status === 'completed');
</script>

<template>
    <Link :href="goals.show(item.id).url">
        <Card class="h-full rounded-4xl gap-2 text-sm hover:bg-accent/50 border-2 border-accent cursor-pointer px-6 py-8 transition-all" :class="{'opacity-60': isCompleted}">
            <div class="flex items-center justify-between w-full">
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
                                <Link :href="goals.edit(item).url" class="cursor-pointer">Edit</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem variant="destructive" as-child>
                                <AlertDialog>
                                    <AlertDialogTrigger>
                                        Delete
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This action cannot be undone. This will permanently delete your goal.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction variant="destructive"
                                                @click="router.delete(goals.destroy(item))">
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
                    <h3 class="font-medium text-xl" :class="{'line-through': isCompleted}">{{ item.title }}</h3>
                    <p class="font-light text-sm">{{ item.description }}</p>
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
                        <span :class="{
                            'text-orange-400 font-semibold': !isCompleted && getDateDiffFromNow(item.deadline) === 0,
                            'text-destructive font-semibold': !isCompleted && getDateDiffFromNow(item.deadline) < 0 
                        }">
                            {{ moment(item.deadline).format('L') }}
                        </span>
                    </span>
                </div>
            </div>
        </Card>
    </Link>
</template>
