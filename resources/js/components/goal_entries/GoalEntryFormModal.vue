<script setup lang="ts">
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Goal, GoalEntry } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import InputError from '../InputError.vue';
import { Button } from '../ui/button';
import { store, update } from '@/actions/App/Http/Controllers/Goals/GoalEntryController.js';

const props = defineProps<{
    goal: Goal;
    record?: GoalEntry;
    open?: boolean;
}>();

const buildDefaults = (record: GoalEntry|undefined) => {
    return {
        increment: record?.increment_value ?? undefined as number | undefined,
        note: record?.note ?? '' as string,
    };
};

const formData = buildDefaults(props.record);

const formState = props.record
    ? {
        formName: null,
        cardTitle: 'goals.entries.form.edit_title',
        cardDescription: 'goals.entries.form.edit_description',
        action: update({ goal: props.goal, goalEntry: props.record }),
        submitBtnLabel: 'goals.entries.form.submit_edit',
    }
    : {
        formName: 'GoalEntryCreateForm',
        cardTitle: 'goals.entries.form.create_title',
        cardDescription: 'goals.entries.form.create_description',
        action: store(props.goal),
        submitBtnLabel: 'goals.entries.form.submit_create',
    };

const form = formState.formName
    ? useForm(formState.formName, formData)
    : useForm(formData);

watch(
    () => props.record,
    () => {
        form.defaults(buildDefaults(props.record))
    }
);

const open = ref<boolean>(props.open ?? false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button>
                    <Plus />
                    {{ $t('goals.actions.log_progress') }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    $t(formState.cardTitle)
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        $t(formState.cardDescription)
                    }}
                </DialogDescription>
            </DialogHeader>
            <form id="log-progress-form" class="space-y-4" @submit.prevent="form.submit(formState.action, {
                onSuccess: () => {
                    form.reset();
                    form.clearErrors();
                    open = false;
                },
            })">
                <div class="space-y-2">
                    <Label for="increment">{{
                        $t('goals.show.progress_value')
                    }}</Label>
                    <div class="flex items-end gap-2">
                        <Input id="increment" v-model="form.increment
                            " type="number" step="0.01" :placeholder="$t(
                                'goals.show.progress_value_placeholder',
                            )
                                " required />
                        <span class="pb-2 text-sm text-muted-foreground">{{ goal.unit }}</span>
                    </div>
                    <InputError :message="form.errors.increment
                        " />
                </div>
                <div class="space-y-2">
                    <Label for="note">{{
                        $t('goals.show.note')
                    }}</Label>
                    <Textarea id="note" v-model="form.note" :placeholder="$t(
                        'goals.show.note_placeholder',
                    )
                        " rows="3" />
                    <InputError :message="form.errors.note" />
                </div>
            </form>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">{{
                        $t('common.actions.cancel')
                    }}</Button>
                </DialogClose>
                <Button type="submit" form="log-progress-form" :disabled="form.processing">
                    {{
                        form.processing
                            ? $t('goals.show.logging')
                            : $t(
                                formState.submitBtnLabel,
                            )
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
