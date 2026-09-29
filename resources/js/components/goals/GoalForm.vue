<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Goals/GoalController';
import { Button } from '@/components/ui/button';
import { FIELD_LIMITS } from '@/lib/field-limits';
import { Goal, User } from '@/types/models';
import { Link, useForm } from '@inertiajs/vue3';
import { GripVertical, Plus, X } from 'lucide-vue-next';
import { computed, useTemplateRef } from 'vue';
import CharacterCounter from '../CharacterCounter.vue';
import InputError from '../InputError.vue';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Textarea } from '../ui/textarea';
// import { Switch } from '../ui/switch';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { useReorderableList } from '@/composables/useReorderableList';
import {
    getGoalDirectionOptions,
    getGoalPolarityOptions,
    getGoalPriorityOptions,
    getGoalRecurrenceOptions,
    getGoalStatusOptions,
    getGoalTypeOptions,
} from '@/lib/form-options';
import { nullToEmpty, nullToUndefined, toDateInputFormat } from '@/lib/utils';
import categories from '@/routes/categories';
import goals from '@/routes/goals';
import InputRequiredIndicator from '../InputRequiredIndicator.vue';
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
    selectedCategory?: string;
}>();

type StagedStep = { key: number; title: string; deadline: string };

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
    category_id:
        props.record?.category_id?.toString() ??
        props.selectedCategory ??
        undefined,
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
        ? toDateInputFormat(props.record.completed_at)
        : undefined,
    status: props.record?.status ?? 'not_started',
    priority: props.record?.priority ?? 'medium',
    polarity: props.record?.polarity ?? 'positive',
    points: props.record?.points ?? 0,
    is_public: props.record?.is_public ?? false,
    order: props.record?.order ?? 0,
    steps: [] as StagedStep[],
};

const form = (
    formState.formName
        ? useForm(formState.formName, formData)
        : useForm(formData)
).withPrecognition(formState.action);

form.transform((data) => ({
    ...data,
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
    steps:
        !props.record && data.type === 'multi_step'
            ? data.steps.map((step) => ({
                  title: step.title,
                  deadline: step.deadline || null,
              }))
            : undefined,
}));

const addStep = () =>
    form.steps.push({
        key: Math.max(0, ...form.steps.map((step) => step.key)) + 1,
        title: '',
        deadline: '',
    });

const removeStep = (index: number) => form.steps.splice(index, 1);

const clearStepErrors = () =>
    form.clearErrors(
        ...(Object.keys(form.errors).filter((field) =>
            field.startsWith('steps.'),
        ) as (keyof typeof formData)[]),
    );

const { move: moveStep } = useReorderableList(
    useTemplateRef<HTMLElement>('stepList'),
    computed({
        get: () => form.steps,
        set: (steps) => (form.steps = steps),
    }),
    { keyOf: (step) => step.key, onReorder: clearStepErrors },
);

const stepError = (index: number, field: 'title' | 'deadline') =>
    (form.errors as Record<string, string | undefined>)[
        `steps.${index}.${field}`
    ];
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
                        <div class="flex items-center justify-between gap-2">
                            <Label for="title">
                                <span>
                                    {{ $t('goals.form.title') }}
                                    <InputRequiredIndicator />
                                </span>
                            </Label>
                            <CharacterCounter
                                :value="form.title"
                                :max="FIELD_LIMITS.goalTitle"
                            />
                        </div>
                        <Input
                            id="title"
                            v-model="form.title"
                            type="text"
                            name="title"
                            aria-required="true"
                            autofocus
                            :tabindex="1"
                            @change="form.validate('title')"
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
                        <Label for="type">
                            <span>
                                {{ $t('goals.form.type') }}
                                <InputRequiredIndicator />
                            </span>
                            <HelpTooltip>
                                {{ $t('goals.form.type_help') }}
                            </HelpTooltip>
                        </Label>
                        <Select
                            id="type"
                            v-model="form.type"
                            name="type"
                            aria-required="true"
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
                        <Label for="current_value">
                            <span>
                                {{ $t('goals.form.current_value') }}
                                <InputRequiredIndicator />
                            </span>
                            <HelpTooltip>
                                {{ $t('goals.form.current_value_help') }}
                            </HelpTooltip>
                        </Label>
                        <Input
                            id="current_value"
                            v-model="form.current_value"
                            type="number"
                            step="0.01"
                            name="current_value"
                            :tabindex="5"
                            aria-required="true"
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
                        <div class="flex items-center justify-between gap-2">
                            <Label for="unit">{{
                                $t('goals.form.unit')
                            }}</Label>
                            <CharacterCounter
                                :value="form.unit"
                                :max="FIELD_LIMITS.goalUnit"
                            />
                        </div>
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
                            @change="form.validate('deadline')"
                        />
                        <InputError :message="form.errors.deadline" />
                    </div>

                    <!-- Completed At -->
                    <div class="grid gap-2">
                        <Label for="completed_at">
                            <span>
                                {{ $t('goals.form.completed_at') }}
                                <InputRequiredIndicator
                                    v-if="form.status === 'completed'"
                                />
                            </span>
                        </Label>
                        <Input
                            id="completed_at"
                            v-model="form.completed_at"
                            type="date"
                            name="completed_at"
                            :tabindex="10"
                            :aria-required="form.status === 'completed'"
                            @change="form.validate('completed_at')"
                        />
                        <InputError :message="form.errors.completed_at" />
                    </div>

                    <!-- Priority -->
                    <div class="grid gap-2">
                        <Label for="priority">
                            <span>
                                {{ $t('goals.form.priority') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <Select
                            id="priority"
                            v-model="form.priority"
                            name="priority"
                            aria-required="true"
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
                        <Label for="status">
                            <span>
                                {{ $t('goals.form.status') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <Select
                            id="status"
                            v-model="form.status"
                            name="status"
                            aria-required="true"
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

                    <!-- Points: hidden until achievements and gamification ship -->
                    <!-- <div class="grid gap-2">
                        <Label for="points">
                            <span>
                                {{ $t('goals.form.points') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <Input
                            id="points"
                            v-model="form.points"
                            type="number"
                            name="points"
                            min="0"
                            :tabindex="13"
                            aria-required="true"
                        />
                        <InputError :message="form.errors.points" />
                    </div> -->

                    <!-- Is Public: FIXME: show only when we handle communities -->
                    <!-- <div class="grid gap-2">
                        <Label for="is_public">
                            <span>
                                {{ $t('goals.form.is_public') }}
                                <InputRequiredIndicator />
                            </span>
                        </Label>
                        <Switch 
                            id="is_public" 
                            v-model="form.is_public"
                            name="is_public" 
                            :tabindex="14"
                            aria-required="true"
                        />
                        <InputError :message="form.errors.is_public" />
                    </div> -->

                    <!-- Direction -->
                    <div class="grid gap-2">
                        <Label for="direction">
                            <span>
                                {{ $t('goals.form.direction') }}
                                <InputRequiredIndicator />
                            </span>
                            <HelpTooltip>
                                {{ $t('goals.form.direction_help') }}
                            </HelpTooltip>
                        </Label>
                        <Select
                            id="direction"
                            v-model="form.direction"
                            name="direction"
                            aria-required="true"
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
                        <Label for="recurrence" class="space-x-2">
                            {{ $t('goals.form.recurrence') }}
                            <HelpTooltip>
                                {{ $t('goals.form.recurrence_help') }}
                            </HelpTooltip>
                        </Label>
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

                    <!-- Steps (multi-step, create only) -->
                    <div
                        v-if="!record && form.type === 'multi_step'"
                        class="col-span-full grid gap-2"
                    >
                        <Label>
                            {{ $t('goals.form.steps') }}
                            <HelpTooltip>
                                {{ $t('goals.form.steps_help') }}
                            </HelpTooltip>
                        </Label>
                        <ol
                            v-if="form.steps.length"
                            ref="stepList"
                            class="grid gap-2"
                        >
                            <li
                                v-for="(step, index) in form.steps"
                                :key="step.key"
                                class="grid gap-1"
                            >
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        :data-drag-handle="step.key"
                                        class="flex size-8 shrink-0 cursor-grab touch-none items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground active:cursor-grabbing"
                                        :aria-label="
                                            $t('goals.form.move_step', {
                                                number: (index + 1).toString(),
                                            })
                                        "
                                        :title="$t('steps.move_handle_hint')"
                                        :tabindex="17"
                                        @keydown.up.prevent="
                                            moveStep(index, index - 1)
                                        "
                                        @keydown.down.prevent="
                                            moveStep(index, index + 1)
                                        "
                                    >
                                        <GripVertical class="size-4" />
                                    </button>
                                    <span
                                        class="w-6 shrink-0 text-right text-sm text-muted-foreground tabular-nums"
                                    >
                                        {{ index + 1 }}.
                                    </span>
                                    <Input
                                        v-model="step.title"
                                        type="text"
                                        :name="`steps[${index}][title]`"
                                        :aria-label="
                                            $t('goals.form.step_title', {
                                                number: (index + 1).toString(),
                                            })
                                        "
                                        :placeholder="
                                            $t('steps.form.title_placeholder')
                                        "
                                        :tabindex="17"
                                    />
                                    <Input
                                        v-model="step.deadline"
                                        type="date"
                                        class="w-auto shrink-0"
                                        :name="`steps[${index}][deadline]`"
                                        :aria-label="
                                            $t('goals.form.step_deadline', {
                                                number: (index + 1).toString(),
                                            })
                                        "
                                        :tabindex="17"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="shrink-0"
                                        :aria-label="
                                            $t('goals.form.remove_step', {
                                                number: (index + 1).toString(),
                                            })
                                        "
                                        :tabindex="17"
                                        @click="removeStep(index)"
                                    >
                                        <X class="size-4" />
                                    </Button>
                                </div>
                                <div
                                    class="ml-18 flex items-start justify-between gap-2"
                                >
                                    <InputError
                                        :message="
                                            stepError(index, 'title') ??
                                            stepError(index, 'deadline')
                                        "
                                    />
                                    <CharacterCounter
                                        class="ml-auto"
                                        :value="step.title"
                                        :max="FIELD_LIMITS.stepTitle"
                                    />
                                </div>
                            </li>
                        </ol>
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :tabindex="17"
                                @click="addStep"
                            >
                                <Plus class="size-4" />
                                {{ $t('steps.add') }}
                            </Button>
                        </div>
                        <InputError :message="form.errors.steps" />
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-between px-6">
                <TextLink :href="goals.index().url" :tabindex="18">
                    {{ $t('common.actions.cancel') }}
                </TextLink>
                <Button
                    :tabindex="19"
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ $t(formState.submitBtnLabel) }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>
