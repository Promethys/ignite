<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Goals/GoalEntryController.js';
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
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '../InputError.vue';
import InputRequiredIndicator from '../InputRequiredIndicator.vue';
import { Button } from '../ui/button';

const props = defineProps<{
    goal: Goal;
    record?: GoalEntry;
    today?: string;
    open?: boolean;
}>();

const isNegative = computed(() => props.goal.polarity === 'negative');
const polarity = computed(() => (isNegative.value ? 'negative' : 'positive'));

// Server-authoritative "today" in the user's stored timezone, with a
// browser-local fallback so the field stays usable without a prop.
const today = computed(
    () => props.today ?? new Date().toLocaleDateString('en-CA'),
);

const recordDate = props.record
    ? String(props.record.entry_date).slice(0, 10)
    : null;

const formState = props.record
    ? {
          title: 'goals.entries.form.edit_title',
          description: 'goals.entries.form.edit_description',
          action: update({ goal: props.goal, goalEntry: props.record }),
          submitLabel: 'goals.entries.form.submit_edit',
      }
    : {
          title: `goals.checkin.title_${polarity.value}`,
          description: `goals.checkin.description_${polarity.value}`,
          action: store(props.goal),
          submitLabel: `goals.checkin.submit_${polarity.value}`,
      };

const formData = {
    entry_date: recordDate ?? today.value,
    note: props.record?.note ?? '',
};

const form = useForm(formData);

const open = ref<boolean>(props.open ?? false);

const submit = () => {
    form.submit(formState.action, {
        onSuccess: () => {
            form.clearErrors();
            open.value = false;

            if (!props.record) {
                form.reset();
            }
        },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button>
                    <Check />
                    {{ $t(`goals.checkin.submit_${polarity}`) }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t(formState.title) }}</DialogTitle>
                <DialogDescription>
                    {{ $t(formState.description) }}
                </DialogDescription>
            </DialogHeader>
            <form
                id="recurring-check-in-form"
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <Label for="entry_date">
                        <span>
                            {{ $t('goals.checkin.date_label') }}
                            <InputRequiredIndicator />
                        </span>
                    </Label>
                    <Input
                        id="entry_date"
                        v-model="form.entry_date"
                        type="date"
                        :max="today"
                        required
                    />
                    <InputError :message="form.errors.entry_date" />
                </div>
                <div class="space-y-2">
                    <Label for="note">{{
                        $t('goals.checkin.note_label')
                    }}</Label>
                    <Textarea
                        id="note"
                        v-model="form.note"
                        :placeholder="$t('goals.checkin.note_placeholder')"
                        rows="3"
                    />
                    <InputError :message="form.errors.note" />
                </div>
            </form>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">{{
                        $t('common.actions.cancel')
                    }}</Button>
                </DialogClose>
                <Button
                    type="submit"
                    form="recurring-check-in-form"
                    :disabled="form.processing"
                >
                    {{
                        form.processing
                            ? $t('goals.checkin.submitting')
                            : $t(formState.submitLabel)
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
