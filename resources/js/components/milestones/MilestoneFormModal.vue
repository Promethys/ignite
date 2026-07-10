<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/MilestoneController';
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
import { Milestone } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Edit, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '../InputError.vue';
import { Button } from '../ui/button';
import { Spinner } from '../ui/spinner';

const props = defineProps<{
    goal_id: number;
    record?: Milestone;
    open?: boolean;
}>();

const formState = props.record
    ? {
          cardTitle: 'milestones.form.edit_title',
          cardDescription: 'milestones.form.edit_description',
          action: update({
              goal: props.record.goal_id,
              milestone: props.record,
          }),
          submitBtnLabel: 'milestones.form.submit_edit',
      }
    : {
          cardTitle: 'milestones.form.create_title',
          cardDescription: 'milestones.form.create_description',
          action: store({ goal: props.goal_id }),
          submitBtnLabel: 'milestones.form.submit_create',
      };

const formData = {
    title: props.record?.title ?? '',
    description: props.record?.description ?? '',
    target_value: props.record?.target_value ?? undefined,
    // points_reward: 0,
};

const form = useForm(formData);

form.transform((data) => ({
    ...data,
    // Convert empty strings back to null for nullable fields
    description: data.description || null,
}));

const open = ref<boolean>(props.open ?? false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button v-if="!record">
                    <Plus />
                    {{ $t('milestones.trigger') }}
                </Button>
                <Button
                    v-else
                    variant="ghost"
                    size="icon"
                    class="size-8 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <Edit class="size-4" />
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ $t(formState.cardTitle) }}</DialogTitle>
                <DialogDescription>{{
                    $t(formState.cardDescription)
                }}</DialogDescription>
            </DialogHeader>
            <form
                @submit.prevent="
                    form.submit(formState.action, {
                        onSuccess: () => {
                            form.reset();
                            form.clearErrors();
                            open = false;
                        },
                    })
                "
            >
                <div class="mb-4 grid gap-4">
                    <div class="grid gap-3">
                        <Label for="title">{{
                            $t('milestones.form.title')
                        }}</Label>
                        <Input
                            id="title"
                            name="title"
                            :placeholder="
                                $t('milestones.form.title_placeholder')
                            "
                            v-model="form.title"
                        />
                        <InputError
                            v-if="form.errors.title"
                            :message="form.errors.title"
                        />
                    </div>
                    <div class="grid gap-3">
                        <Label for="description">{{
                            $t('milestones.form.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :placeholder="
                                $t('milestones.form.description_placeholder')
                            "
                            v-model="form.description"
                        />
                        <InputError
                            v-if="form.errors.description"
                            :message="form.errors.description"
                        />
                    </div>
                    <div class="grid gap-3">
                        <div class="space-y-3">
                            <Label for="target_value">{{
                                $t('milestones.form.target_value')
                            }}</Label>
                            <Input
                                id="target_value"
                                name="target_value"
                                type="number"
                                v-model="form.target_value"
                            />
                            <InputError
                                v-if="form.errors.target_value"
                                :message="form.errors.target_value"
                            />
                        </div>
                        <!-- <div class="space-y-3">
                            <Label for="icon">Points Reward</Label>
                            <Input
                                id="icon"
                                name="icon"
                                type="number"
                                min="0"
                                v-model="form.points_reward"
                            />
                            <InputError
                                v-if="form.errors.points_reward"
                                :message="form.errors.points_reward"
                            />
                        </div> -->
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">
                            {{ $t('common.actions.cancel') }}
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        <template v-if="form.processing">
                            <Spinner />
                        </template>
                        {{ $t(formState.submitBtnLabel) }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
