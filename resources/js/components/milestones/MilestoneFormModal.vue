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
import { Goal, Milestone } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Edit, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '../InputError.vue';
import { Button } from '../ui/button';
import { Spinner } from '../ui/spinner';

const props = defineProps<{
    goal: Goal;
    record?: Milestone;
    open?: boolean;
}>();

const formState = props.record
    ? {
          formName: null,
          cardTitle: 'Edit a milestone',
          cardDescription: 'Edit your milestone.',
          action: update({ goal: props.goal, milestone: props.record }),
          submitBtnLabel: 'Edit',
      }
    : {
          formName: 'MilestoneCreateForm',
          cardTitle: 'Create a milestone',
          cardDescription:
              'Create a milestone and track key checkpoints on your journey to completing this goal.',
          action: store({ goal: props.goal }),
          submitBtnLabel: 'Create',
      };

const formData = {
    title: props.record?.title ?? '',
    description: props.record?.description ?? '',
    target_value: props.record?.target_value ?? undefined,
    points_reward: 0,
};

const form = formState.formName
    ? useForm(formState.formName, formData)
    : useForm(formData);

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
                    Milestone
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
                <DialogTitle>{{ formState.cardTitle }}</DialogTitle>
                <DialogDescription>{{
                    formState.cardDescription
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
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            name="title"
                            placeholder="Reach 25% completion"
                            v-model="form.title"
                        />
                        <InputError
                            v-if="form.errors.title"
                            :message="form.errors.title"
                        />
                    </div>
                    <div class="grid gap-3">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            default-value="All sportive goals like soccer, tennis, gym, ..."
                            v-model="form.description"
                        />
                        <InputError
                            v-if="form.errors.description"
                            :message="form.errors.description"
                        />
                    </div>
                    <div class="grid gap-3">
                        <div class="space-y-3">
                            <Label for="target_value">Target Value</Label>
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
                        <Button variant="outline"> Cancel </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        <template v-if="form.processing">
                            <Spinner />
                        </template>
                        {{ formState.submitBtnLabel }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
