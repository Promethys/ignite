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
import { Button } from './ui/button';
import { Ellipsis } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from './ui/dropdown-menu';
import GoalBadges from './GoalBadges.vue';

defineProps<{
    item: Goal;
    variant: string;
}>();
</script>

<template>
    <Card class="rounded-4xl gap-2 text-sm hover:bg-accent border-2 border-accent cursor-pointer" :class="(variant && variant === 'mini') ? 'px-6 py-8' : 'px-9 py-12 min-w-md'">
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
                        <DropdownMenuItem as-child v-if="item.type === 'simple'">
                            <Link :method="goals.complete(item).method" :href="goals.complete(item).url" class="cursor-pointer">Mark as completed</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="goals.edit(item).url" class="cursor-pointer">Edit</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
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

        <div class="space-y-9">
            <div class="space-y-2">
                <h3 class="font-medium text-xl hover:underline"  @click="router.get(goals.show(item))">{{ item.title }}</h3>
                <p class="font-light text-sm">{{ item.description }}</p>
            </div>
            <div>
                <!-- Details here: progress bar and others -->
            </div>
        </div>
    </Card>
</template>
