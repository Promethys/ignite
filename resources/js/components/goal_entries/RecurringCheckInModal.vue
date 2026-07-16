<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Goals/GoalEntryController.js';
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
import { Goal } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '../InputError.vue';
import { Button } from '../ui/button';

const props = defineProps<{
    goal: Goal;
    open?: boolean;
}>();

const isNegative = computed(() => props.goal.polarity === 'negative');
const polarity = computed(() => (isNegative.value ? 'negative' : 'positive'));

const today = () => new Date().toISOString().slice(0, 10);

const form = useForm('RecurringCheckInForm', {
    entry_date: today(),
    note: '',
});

const open = ref<boolean>(props.open ?? false);

const submit = () => {
    form.submit(store(props.goal), {
        onSuccess: () => {
            form.reset();
            form.clearErrors();
            open.value = false;
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
                <DialogTitle>{{
                    $t(`goals.checkin.title_${polarity}`)
                }}</DialogTitle>
                <DialogDescription>
                    {{ $t(`goals.checkin.description_${polarity}`) }}
                </DialogDescription>
            </DialogHeader>
            <form
                id="recurring-check-in-form"
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <Label for="entry_date">{{
                        $t('goals.checkin.date_label')
                    }}</Label>
                    <Input
                        id="entry_date"
                        v-model="form.entry_date"
                        type="date"
                        :max="today()"
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
                            : $t(`goals.checkin.submit_${polarity}`)
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
