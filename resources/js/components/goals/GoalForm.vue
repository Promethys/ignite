<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Goals/GoalController';
import { Button } from '@/components/ui/button';
import { Goal, User } from '@/types/models';
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '../InputError.vue';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Textarea } from '../ui/textarea';
// import { Switch } from '../ui/switch';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import {
    getGoalDirectionOptions,
    getGoalPolarityOptions,
    getGoalPriorityOptions,
    getGoalRecurrenceOptions,
    getGoalStatusOptions,
    getGoalTypeOptions,
} from '@/lib/form-options';
import { nullToEmpty, nullToUndefined } from '@/lib/utils';
import categories from '@/routes/categories';
import goals from '@/routes/goals';
import TextLink from '../TextLink.vue';
import HelpTooltip from '../ui/HelpTooltip.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';

const props = defineProps<{
    record?: Goal;
    user: User;
}>();

const formState = props.record
    ? {
          formName: null,
          action: update(props.record),
          submitBtnLabel: 'goals.form.submit_edit',
      }
    : {
          formName: 'GoalCreateForm',
          action: store(),
          submitBtnLabel: 'goals.form.submit_create',
      };

const formData = {
    category_id: props.record?.category_id?.toString() ?? undefined,
    title: nullToEmpty(props.record?.title),
    description: nullToEmpty(props.record?.description),
    icon: nullToEmpty(props.record?.icon),
    type: props.record?.type ?? 'simple',
    direction: props.record?.direction ?? 'ascending',
    target_value: nullToUndefined(props.record?.target_value),
    current_value: props.record?.current_value ?? 0,
    unit: nullToEmpty(props.record?.unit),
    recurrence: props.record?.recurrence ?? undefined,
    start_date: props.record?.start_date
        ? new Date(props.record?.start_date).toISOString().split('T')[0]
        : undefined,
    deadline: props.record?.deadline
        ? new Date(props.record?.deadline).toISOString().split('T')[0]
        : undefined,
    completed_at: props.record?.completed_at
        ? new Date(props.record?.completed_at).toISOString().split('T')[0]
        : undefined,
    status: props.record?.status ?? 'not_started',
    priority: props.record?.priority ?? 'medium',
    polarity: props.record?.polarity ?? 'positive',
    points: props.record?.points ?? 0,
    is_public: props.record?.is_public ?? false,
    order: props.record?.order ?? 0,
};

const form = formState.formName
    ? useForm(formState.formName, formData)
    : useForm(formData);

form.transform((data) => ({
    ...data,
    user_id: props.user.id,
    // Convert empty strings back to null for nullable fields
    description: data.description || null,
    icon: data.icon || null,
    unit: data.type === 'quantifiable' ? data.unit : null,
    start_date: data.start_date || null,
    deadline: data.deadline || null,
    completed_at: data.completed_at || null,
    category_id: data.category_id || null,
    target_value: data.type === 'quantifiable' ? data.target_value : null,
    recurrence: data.type === 'recurring' ? data.recurrence : null,
}));
</script>

<template>
    <form @submit.prevent="form.submit(formState.action)">
        <Card>
            <CardContent>
                <div
                    class="grid gap-6 sm:grid-cols-1 sm:gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Title -->
                    <div class="grid gap-2">
                        <Label for="title">{{ $t('goals.form.title') }}</Label>
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
                            <Label
                                for="category_id"
                                class="w-full justify-between"
                            >
                                <span>{{ $t('goals.form.category') }}</span>
                                <Link
                                    class="hover:underline"
                                    :tabindex="-1"
                                    :href="categories.index()"
                                    :data="{ create: 1 }"
                                >
                                    {{ $t('goals.form.create_category') }}
                                </Link>
                            </Label>
                        </div>
                        <Select
                            id="category_id"
                            v-model="form.category_id"
                            name="category_id"
                            :disabled="
                                !user.categories ||
                                user.categories?.length === 0
                            "
                        >
                            <SelectTrigger :tabindex="2">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_category')
                                    "
                                />
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
                        <Label for="type">{{ $t('goals.form.type') }}</Label>
                        <Select
                            id="type"
                            v-model="form.type"
                            name="type"
                            required
                        >
                            <SelectTrigger :tabindex="3">
                                <SelectValue
                                    :placeholder="$t('goals.form.select_type')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in getGoalTypeOptions()"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ $t(type.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Description -->
                    <div class="col-span-full grid gap-2">
                        <Label for="description">{{
                            $t('goals.form.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            name="description"
                            :tabindex="4"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <!-- Current Value -->
                    <div class="grid gap-2">
                        <Label for="current_value">{{
                            $t('goals.form.current_value')
                        }}</Label>
                        <Input
                            id="current_value"
                            v-model="form.current_value"
                            type="number"
                            step="0.01"
                            name="current_value"
                            :tabindex="5"
                            required
                        />
                        <InputError :message="form.errors.current_value" />
                    </div>

                    <!-- Target Value -->
                    <div class="grid gap-2">
                        <Label for="target_value">{{
                            $t('goals.form.target_value')
                        }}</Label>
                        <Input
                            id="target_value"
                            v-model="form.target_value"
                            type="number"
                            step="0.01"
                            name="target_value"
                            :tabindex="6"
                            :disabled="form.type !== 'quantifiable'"
                        />
                        <InputError :message="form.errors.target_value" />
                    </div>

                    <!-- Unit -->
                    <div class="grid gap-2">
                        <Label for="unit">{{ $t('goals.form.unit') }}</Label>
                        <Input
                            id="unit"
                            v-model="form.unit"
                            type="text"
                            name="unit"
                            :placeholder="$t('goals.form.unit_placeholder')"
                            :tabindex="7"
                            :disabled="form.type !== 'quantifiable'"
                        />
                        <InputError :message="form.errors.unit" />
                    </div>

                    <!-- Start Date -->
                    <div class="grid gap-2">
                        <Label for="start_date">{{
                            $t('goals.form.start_date')
                        }}</Label>
                        <Input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            name="start_date"
                            :tabindex="8"
                        />
                        <InputError :message="form.errors.start_date" />
                    </div>

                    <!-- Deadline -->
                    <div class="grid gap-2">
                        <Label for="deadline">{{
                            $t('goals.form.deadline')
                        }}</Label>
                        <Input
                            id="deadline"
                            v-model="form.deadline"
                            type="date"
                            name="deadline"
                            :tabindex="9"
                        />
                        <InputError :message="form.errors.deadline" />
                    </div>

                    <!-- Completed At -->
                    <div class="grid gap-2">
                        <Label for="completed_at">{{
                            $t('goals.form.completed_at')
                        }}</Label>
                        <Input
                            id="completed_at"
                            v-model="form.completed_at"
                            type="date"
                            name="completed_at"
                            :tabindex="10"
                            :required="form.status === 'completed'"
                        />
                        <InputError :message="form.errors.completed_at" />
                    </div>

                    <!-- Priority -->
                    <div class="grid gap-2">
                        <Label for="priority">{{
                            $t('goals.form.priority')
                        }}</Label>
                        <Select
                            id="priority"
                            v-model="form.priority"
                            name="priority"
                            required
                        >
                            <SelectTrigger :tabindex="11">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_priority')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="priority in getGoalPriorityOptions()"
                                    :key="priority.value"
                                    :value="priority.value"
                                >
                                    {{ $t(priority.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label for="status">{{
                            $t('goals.form.status')
                        }}</Label>
                        <Select
                            id="status"
                            v-model="form.status"
                            name="status"
                            required
                        >
                            <SelectTrigger :tabindex="12">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_status')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in getGoalStatusOptions()"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ $t(status.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <!-- Points -->
                    <div class="grid gap-2">
                        <Label for="points">{{
                            $t('goals.form.points')
                        }}</Label>
                        <Input
                            id="points"
                            v-model="form.points"
                            type="number"
                            name="points"
                            min="0"
                            :tabindex="13"
                            required
                        />
                        <InputError :message="form.errors.points" />
                    </div>

                    <!-- Is Public: FIXME: show only when we handle communities -->
                    <!-- <div class="grid gap-2">
                        <Label for="is_public">Is public</Label>
                        <Switch 
                            id="is_public" 
                            v-model="form.is_public"
                            name="is_public" 
                            :tabindex="14"
                        />
                        <InputError :message="form.errors.is_public" />
                    </div> -->

                    <!-- Direction -->
                    <div class="grid gap-2">
                        <Label for="direction" class="space-x-2">
                            {{ $t('goals.form.direction') }}
                            <HelpTooltip>
                                {{ $t('goals.form.direction_help') }}
                            </HelpTooltip>
                        </Label>
                        <Select
                            id="direction"
                            v-model="form.direction"
                            name="direction"
                        >
                            <SelectTrigger :tabindex="14">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_direction')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="direction in getGoalDirectionOptions()"
                                    :key="direction.value"
                                    :value="direction.value"
                                >
                                    {{ $t(direction.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.direction" />
                    </div>

                    <!-- Polarity -->
                    <div class="grid gap-2">
                        <Label for="polarity" class="space-x-2">
                            {{ $t('goals.form.polarity') }}
                            <HelpTooltip>
                                {{ $t('goals.form.polarity_help') }}
                            </HelpTooltip>
                        </Label>
                        <Select
                            id="polarity"
                            v-model="form.polarity"
                            name="polarity"
                            :disabled="form.type !== 'recurring'"
                        >
                            <SelectTrigger :tabindex="15">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_polarity')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="polarity in getGoalPolarityOptions()"
                                    :key="polarity.value"
                                    :value="polarity.value"
                                >
                                    {{ $t(polarity.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.polarity" />
                    </div>

                    <!-- Recurrence -->
                    <div class="grid gap-2">
                        <Label for="recurrence">{{
                            $t('goals.form.recurrence')
                        }}</Label>
                        <Select
                            id="recurrence"
                            v-model="form.recurrence"
                            name="recurrence"
                            :disabled="form.type !== 'recurring'"
                        >
                            <SelectTrigger :tabindex="16">
                                <SelectValue
                                    :placeholder="
                                        $t('goals.form.select_recurrence')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="recurrence in getGoalRecurrenceOptions()"
                                    :key="recurrence.value"
                                    :value="recurrence.value"
                                >
                                    {{ $t(recurrence.label) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.recurrence" />
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-between px-6">
                <TextLink :href="goals.index().url" :tabindex="17">
                    {{ $t('common.actions.cancel') }}
                </TextLink>
                <Button
                    :tabindex="18"
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ $t(formState.submitBtnLabel) }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>
