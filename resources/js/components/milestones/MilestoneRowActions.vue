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

defineProps<{
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
                <Button>{{ $t('common.actions.edit') }}</Button>
            </template>
        </MilestoneFormModal>

        <Dialog>
            <DialogTrigger as-child>
                <Button variant="destructive">
                    {{ $t('common.actions.delete') }}
                </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{
                        $t('milestones.row.delete_title')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            $t('milestones.row.delete_description', {
                                title: row.original.title,
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            {{ $t('common.actions.cancel') }}
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
                        {{ $t('milestones.row.delete_confirm') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
