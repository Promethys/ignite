<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Goal, GoalEntry } from '@/types/models';
import { Ellipsis, Pencil, Trash } from 'lucide-vue-next';
import { computed } from 'vue';
import DeleteEntryDialog from './DeleteEntryDialog.vue';
import GoalEntryFormModal from './GoalEntryFormModal.vue';
import RecurringCheckInModal from './RecurringCheckInModal.vue';

const props = defineProps<{
    goal: Goal;
    record: GoalEntry;
    today?: string;
}>();

const isRecurring = computed(() => props.goal.type === 'recurring');
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="size-7 text-muted-foreground"
            >
                <Ellipsis />
                <span class="sr-only">{{ $t('common.actions.more') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuGroup>
                <RecurringCheckInModal v-if="isRecurring" :goal :record :today>
                    <template #trigger>
                        <DropdownMenuItem @select.prevent>
                            <Pencil />
                            {{ $t('common.actions.edit') }}
                        </DropdownMenuItem>
                    </template>
                </RecurringCheckInModal>
                <GoalEntryFormModal v-else :goal :record>
                    <template #trigger>
                        <DropdownMenuItem @select.prevent>
                            <Pencil />
                            {{ $t('common.actions.edit') }}
                        </DropdownMenuItem>
                    </template>
                </GoalEntryFormModal>
                <DeleteEntryDialog :goal :record>
                    <template #trigger>
                        <DropdownMenuItem variant="destructive" @select.prevent>
                            <Trash />
                            {{ $t('common.actions.delete') }}
                        </DropdownMenuItem>
                    </template>
                </DeleteEntryDialog>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
