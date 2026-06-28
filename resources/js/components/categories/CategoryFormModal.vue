<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/CategoryController';
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
import { Category } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Edit, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '../InputError.vue';
import { Button } from '../ui/button';
import { Spinner } from '../ui/spinner';

const props = defineProps<{
    record?: Category;
    open?: boolean;
}>();

const formState = props.record
    ? {
          formName: null,
          cardTitle: 'Edit a category',
          cardDescription: 'Edit your category.',
          action: update(props.record),
          submitBtnLabel: 'Edit',
      }
    : {
          formName: 'CategoryCreateForm',
          cardTitle: 'Create a category',
          cardDescription:
              'Create a category here. You can use it to organize your goals.',
          action: store(),
          submitBtnLabel: 'Create',
      };

const formData = {
    name: props.record?.name ?? '',
    description: props.record?.description ?? '',
    icon: props.record?.icon ?? '',
    color: props.record?.color ?? undefined,
};

const form = formState.formName
    ? useForm(formState.formName, formData)
    : useForm(formData);

form.transform((data) => ({
    ...data,
    // Convert empty strings back to null for nullable fields
    description: data.description || null,
    icon: data.icon || null,
}));

const open = ref<boolean>(props.open ?? false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button v-if="!record">
                <Plus />
                Category
            </Button>
            <Button
                v-else
                variant="ghost"
                size="icon"
                class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <Edit class="h-4 w-4" />
            </Button>
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
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="Sports"
                            v-model="form.name"
                        />
                        <InputError
                            v-if="form.errors.name"
                            :message="form.errors.name"
                        />
                    </div>
                    <div class="grid gap-3">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            placeholder="All sportive goals like soccer, tennis, gym, ..."
                            v-model="form.description"
                        />
                        <InputError
                            v-if="form.errors.description"
                            :message="form.errors.description"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-3">
                            <Label for="color">Color</Label>
                            <Input
                                id="color"
                                name="color"
                                type="color"
                                default-value="#ff0000"
                                v-model="form.color"
                            />
                            <InputError
                                v-if="form.errors.color"
                                :message="form.errors.color"
                            />
                        </div>
                        <div class="space-y-3">
                            <Label for="icon">Icon</Label>
                            <Input
                                id="icon"
                                name="icon"
                                placeholder="💪"
                                v-model="form.icon"
                            />
                            <InputError
                                v-if="form.errors.icon"
                                :message="form.errors.icon"
                            />
                        </div>
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
