<script setup lang="ts">
import { store, update } from '@/actions/App/Http/Controllers/Goals/GoalController';
import { Goal, User } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import InputError from '../InputError.vue';
import { Textarea } from '../ui/textarea';
import { Button } from '@/components/ui/button';
import { Switch } from '../ui/switch';
import goals from '@/routes/goals';
import TextLink from '../TextLink.vue';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '../ui/select';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle
} from '@/components/ui/card';
import { 
    getGoalPriorityOptions, 
    getGoalRecurrenceOptions, 
    getGoalStatusOptions, 
    getGoalTypeOptions 
} from '@/lib/form-options';
import { nullToEmpty, nullToUndefined } from '@/lib/utils';

const props = defineProps<{
    record?: Goal;
    user: User;
}>();

const formState = props.record ? {
    cardTitle: 'Edit a goal',
    cardDescription: 'Edit your goal.',
    action: update(props.record),
    submitBtnLabel: 'Edit',
} : {
    cardTitle: 'Create a goal',
    cardDescription: 'Create your new goal.',
    action: store(),
    submitBtnLabel: 'Create',
};

const form = useForm({
    category_id: props.record?.category_id?.toString() ?? undefined,
    title: nullToEmpty(props.record?.title),
    description: nullToEmpty(props.record?.description),
    icon: nullToEmpty(props.record?.icon),
    type: props.record?.type ?? 'simple',
    target_value: nullToUndefined(props.record?.target_value),
    current_value: props.record?.current_value ?? 0,
    unit: nullToEmpty(props.record?.unit),
    recurrence: props.record?.recurrence ?? undefined,
    start_date: props.record?.start_date ? new Date(props.record?.start_date).toISOString().split('T')[0] : undefined,
    deadline: props.record?.deadline ? new Date(props.record?.deadline).toISOString().split('T')[0] : undefined,
    completed_at: props.record?.completed_at ? new Date(props.record?.completed_at).toISOString().split('T')[0] : undefined,
    status: props.record?.status ?? 'not_started',
    priority: props.record?.priority ?? 'medium',
    points: props.record?.points ?? 0,
    is_public: props.record?.is_public ?? false,
    order: props.record?.order ?? 0,
})
.transform((data) => ({
    ...data,
    user_id: props.user.id,
    // Convert empty strings back to null for nullable fields
    description: data.description || null,
    icon: data.icon || null,
    unit: (data.type === 'quantifiable') ? data.unit : null,
    start_date: data.start_date || null,
    deadline: data.deadline || null,
    completed_at: data.completed_at || null,
    category_id: data.category_id || null,
    target_value: (data.type === 'quantifiable') ? data.target_value : null,
    recurrence: (data.type === 'recurring') ? data.recurrence : null,
}));
</script>

<template>
    <form @submit.prevent="form.submit(formState.action)">
        <Card class="m-4">
            <CardHeader>
                <CardTitle>{{ formState.cardTitle }}</CardTitle>
                <CardDescription>{{ formState.cardDescription }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-6 lg:grid-cols-3 md:grid-cols-2 sm:grid-cols-1 sm:gap-4">
                    <!-- Title -->
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            type="text"
                            name="title"
                            required
                            autofocus
                            :tabindex="1"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="category_id">Category</Label>
                            <span class="text-sm" :tabindex="3">
                                Create a category
                            </span>
                        </div>
                        <Select 
                            id="category_id" 
                            v-model="form.category_id"
                            name="category_id" 
                            :tabindex="2"
                            :disabled="!user.categories || user.categories?.length === 0"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="(category, id) in user.categories" 
                                    :key="id" 
                                    :value="id"
                                >
                                    {{ category }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <!-- Type -->
                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <Select 
                            id="type" 
                            v-model="form.type"
                            name="type" 
                            :tabindex="4"
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a goal type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="type in getGoalTypeOptions()" 
                                    :key="type.value" 
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2 col-span-full">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            name="description"
                            :tabindex="5"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <!-- Current Value -->
                    <div class="grid gap-2">
                        <Label for="current_value">Current value</Label>
                        <Input
                            id="current_value"
                            v-model="form.current_value"
                            type="number"
                            step="0.01"
                            name="current_value"
                            :tabindex="6"
                            required
                        />
                        <InputError :message="form.errors.current_value" />
                    </div>

                    <!-- Target Value -->
                    <div class="grid gap-2">
                        <Label for="target_value">Target value</Label>
                        <Input
                            id="target_value"
                            v-model="form.target_value"
                            type="number"
                            step="0.01"
                            name="target_value"
                            :tabindex="7"
                            :disabled="form.type !== 'quantifiable'"
                        />
                        <InputError :message="form.errors.target_value" />
                    </div>

                    <!-- Unit -->
                    <div class="grid gap-2">
                        <Label for="unit">Unit</Label>
                        <Input
                            id="unit"
                            v-model="form.unit"
                            type="text"
                            name="unit"
                            placeholder="km, books, sessions, etc."
                            :tabindex="8"
                            :disabled="form.type !== 'quantifiable'"
                        />
                        <InputError :message="form.errors.unit" />
                    </div>

                    <!-- Start Date -->
                    <div class="grid gap-2">
                        <Label for="start_date">Start date</Label>
                        <Input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            name="start_date"
                            :tabindex="9"
                        />
                        <InputError :message="form.errors.start_date" />
                    </div>

                    <!-- Deadline -->
                    <div class="grid gap-2">
                        <Label for="deadline">Deadline</Label>
                        <Input
                            id="deadline"
                            v-model="form.deadline"
                            type="date"
                            name="deadline"
                            :tabindex="10"
                        />
                        <InputError :message="form.errors.deadline" />
                    </div>

                    <!-- Completed At -->
                    <div class="grid gap-2">
                        <Label for="completed_at">Completed at</Label>
                        <Input
                            id="completed_at"
                            v-model="form.completed_at"
                            type="date"
                            name="completed_at"
                            :tabindex="11"
                            :required="form.status === 'completed'"
                        />
                        <InputError :message="form.errors.completed_at" />
                    </div>

                    <!-- Priority -->
                    <div class="grid gap-2">
                        <Label for="priority">Priority</Label>
                        <Select 
                            id="priority" 
                            v-model="form.priority"
                            name="priority" 
                            :tabindex="12"
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a goal priority" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="priority in getGoalPriorityOptions()" 
                                    :key="priority.value" 
                                    :value="priority.value"
                                >
                                    {{ priority.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <Select 
                            id="status" 
                            v-model="form.status"
                            name="status" 
                            :tabindex="13"
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="status in getGoalStatusOptions()" 
                                    :key="status.value" 
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <!-- Points -->
                    <div class="grid gap-2">
                        <Label for="points">Points</Label>
                        <Input
                            id="points"
                            v-model="form.points"
                            type="number"
                            name="points"
                            min="0"
                            :tabindex="14"
                            required
                        />
                        <InputError :message="form.errors.points" />
                    </div>

                    <!-- Is Public -->
                    <div class="grid gap-2">
                        <Label for="is_public">Is public</Label>
                        <Switch 
                            id="is_public" 
                            v-model="form.is_public"
                            name="is_public" 
                            :tabindex="15"
                        />
                        <InputError :message="form.errors.is_public" />
                    </div>

                    <!-- Icon -->
                    <div class="grid gap-2">
                        <Label for="icon">Icon</Label>
                        <Input
                            id="icon"
                            v-model="form.icon"
                            type="text"
                            name="icon"
                            placeholder="📕, 🚀, 🏃‍♀️"
                            :tabindex="16"
                        />
                        <InputError :message="form.errors.icon" />
                    </div>

                    <!-- Recurrence -->
                    <div class="grid gap-2">
                        <Label for="recurrence">Recurrence</Label>
                        <Select 
                            id="recurrence" 
                            v-model="form.recurrence"
                            name="recurrence" 
                            :tabindex="17"
                            :disabled="form.type !== 'recurring'"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a recurrence" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="recurrence in getGoalRecurrenceOptions()" 
                                    :key="recurrence.value" 
                                    :value="recurrence.value"
                                >
                                    {{ recurrence.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.recurrence" />
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-between px-6">
                <TextLink :href="goals.index().url" :tabindex="18">
                    Cancel
                </TextLink>
                <Button 
                    :tabindex="19" 
                    type="submit" 
                    :disabled="form.processing"
                >
                    {{ formState.submitBtnLabel }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>
