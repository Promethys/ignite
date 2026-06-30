<script setup lang="ts">
import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import milestones from '@/routes/milestones';
import { Milestone } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Row } from '@tanstack/vue-table';
import { Button } from '../ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '../ui/dialog';

const props = defineProps<{
    row: Row<Milestone>;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-center gap-2">
        <MilestoneFormModal
            :goal_id="row.original.goal_id"
            :record="row.original"
        >
            <template #trigger>
                <Button>Edit</Button>
            </template>
        </MilestoneFormModal>

        <Dialog>
            <DialogTrigger as-child>
                <Button variant="destructive"> Delete </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Delete this milestone?</DialogTitle>
                    <DialogDescription>
                        "{{ row.original.title }}" will be permanently removed
                        from this goal. This can't be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="
                            router.delete(
                                milestones.destroy({
                                    goal: row.original.goal_id,
                                    milestone: row.original.id,
                                }),
                                {
                                    preserveScroll: true,
                                },
                            )
                        "
                    >
                        Delete milestone
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
