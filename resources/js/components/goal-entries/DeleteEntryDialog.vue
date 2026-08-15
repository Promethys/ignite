<script setup lang="ts">
import { destroy } from '@/actions/App/Http/Controllers/Goals/GoalEntryController.js';
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
import { Goal, GoalEntry } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Trash } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '../ui/button';

const props = defineProps<{
    goal: Goal;
    record: GoalEntry;
}>();

const open = ref(false);

const deleteEntry = () => {
    router.delete(destroy({ goal: props.goal, goalEntry: props.record }), {
        onSuccess: () => (open.value = false),
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button variant="destructive" size="sm">
                    <Trash />
                    {{ $t('common.actions.delete') }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>
                    {{ $t('goals.entries.delete_title') }}
                </DialogTitle>
                <DialogDescription>
                    {{ $t('goals.entries.delete_description') }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">
                        {{ $t('common.actions.cancel') }}
                    </Button>
                </DialogClose>
                <Button variant="destructive" @click="deleteEntry">
                    {{ $t('common.actions.delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
