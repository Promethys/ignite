<script setup lang="ts">
import { useReorderableList } from '@/composables/useReorderableList';
import { formatDate, getDateDiffFromNow } from '@/lib/utils';
import milestones from '@/routes/milestones';
import { Goal, Milestone } from '@/types/models';
import { router } from '@inertiajs/vue3';
import {
    Calendar,
    Check,
    GripVertical,
    Plus,
    RotateCcw,
    Target,
} from 'lucide-vue-next';
import moment from 'moment';
import { computed, ref, useTemplateRef, watch } from 'vue';
import { Badge } from '../ui/badge';
import MilestoneFormModal from './MilestoneFormModal.vue';

const props = defineProps<{
    record: Goal;
}>();

const labelNamespace =
    props.record.type === 'multi_step' ? 'steps' : 'milestones';

const isReorderable = computed(() => props.record.type === 'multi_step');

const orderedMilestones = ref<Milestone[]>([
    ...(props.record.milestones ?? []),
]);

watch(
    () => props.record.milestones,
    (milestones) => {
        orderedMilestones.value = [...(milestones ?? [])];
    },
);

const saveOrder = (reordered: Milestone[]) => {
    router.patch(
        milestones.reorder({ goal: props.record }),
        { milestones: reordered.map((milestone) => milestone.id) },
        {
            preserveScroll: true,
            onError: () => {
                orderedMilestones.value = [...(props.record.milestones ?? [])];
            },
        },
    );
};

const { move: moveWithKeyboard } = useReorderableList(
    useTemplateRef<HTMLElement>('listElement'),
    orderedMilestones,
    {
        keyOf: (milestone) => milestone.id,
        enabled: isReorderable.value,
        onReorder: saveOrder,
    },
);

const deadlineState = (milestone: Milestone) => {
    if (!milestone.deadline || isCompleted(milestone)) return 'none';
    const diff = getDateDiffFromNow(milestone.deadline);
    if (diff < 0) return 'overdue';
    if (diff === 0) return 'due';
    return 'ok';
};

const toggleMilestone = (milestone: Milestone) => {
    const url = isCompleted(milestone)
        ? milestones.uncomplete({ goal: props.record, milestone })
        : milestones.complete({ goal: props.record, milestone });

    router.patch(url);
};

const isAutoComplete = (milestone: Milestone) => {
    return milestone.target_value != null;
};

const isAutoCompleted = (milestone: Milestone) => milestone.is_reached;

const isCompleted = (milestone: Milestone) => {
    return milestone.is_completed || isAutoCompleted(milestone);
};

const getProgress = (milestone: Milestone) => {
    if (!milestone.target_value) return 0;
    return Math.min(
        100,
        (props.record.current_value / milestone.target_value) * 100,
    );
};

const activeIndex = computed(() =>
    orderedMilestones.value.findIndex(
        (milestone) => !milestone.is_completed && !isAutoCompleted(milestone),
    ),
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="relative flex flex-col">
            <div ref="listElement" class="flex flex-col">
                <div
                    v-for="(milestone, index) in orderedMilestones"
                    :key="milestone.id"
                    class="group/step relative flex gap-4"
                >
                    <div
                        class="absolute top-8 left-4 h-[calc(100%-16px)] w-0.5"
                        :class="{
                            'bg-success': isCompleted(milestone),
                            'bg-border': !isCompleted(milestone),
                        }"
                        v-if="index !== orderedMilestones.length - 1"
                    />

                    <!-- Circle Indicator -->
                    <div class="relative z-10 flex-shrink-0">
                        <button
                            @click="
                                () =>
                                    !isAutoComplete(milestone) &&
                                    toggleMilestone(milestone)
                            "
                            :aria-label="
                                !isAutoComplete(milestone)
                                    ? isCompleted(milestone)
                                        ? $t('milestones.mark_incomplete')
                                        : $t('milestones.mark_complete')
                                    : undefined
                            "
                            :class="{
                                'group flex size-8 items-center justify-center rounded-full border-2 transition-all': true,
                                'cursor-pointer': !isAutoComplete(milestone),
                                'cursor-default': isAutoComplete(milestone),
                                'border-success bg-success text-success-foreground':
                                    isCompleted(milestone),
                                'border-warning bg-warning/20 ring-4 ring-warning/20':
                                    index === activeIndex &&
                                    !isCompleted(milestone),
                                'border-border bg-background':
                                    index !== activeIndex &&
                                    !isCompleted(milestone),
                                'hover:border-success':
                                    !isAutoComplete(milestone) &&
                                    !isCompleted(milestone),
                                'hover:border-warning':
                                    !isAutoComplete(milestone) &&
                                    isCompleted(milestone),
                            }"
                        >
                            <template v-if="isCompleted(milestone)">
                                <Check
                                    class="inline size-4 group-hover:hidden"
                                />
                                <RotateCcw
                                    class="hidden size-4 group-hover:inline"
                                />
                            </template>
                            <template v-else>
                                <!-- Auto-completing (quantifiable) milestone: non-interactive progress ring -->
                                <div v-if="isAutoComplete(milestone)">
                                    <div
                                        class="size-5 rounded-full border-2 border-muted-foreground"
                                        :style="{
                                            background: `conic-gradient(var(--warning) ${getProgress(milestone)}%, transparent ${getProgress(milestone)}%)`,
                                        }"
                                    />
                                </div>
                                <!-- Manual step: empty checkbox that previews a check on hover -->
                                <Check
                                    v-else
                                    class="size-4 text-success opacity-0 transition-opacity group-hover:opacity-100"
                                />
                            </template>
                        </button>
                    </div>

                    <!-- Content -->
                    <div
                        :class="{
                            'flex flex-1 flex-col gap-1 pb-6': true,
                            'opacity-60': isCompleted(milestone),
                        }"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{
                                    'text-warning': index === activeIndex,
                                    'line-through': isCompleted(milestone),
                                }"
                            >
                                {{ milestone.title }}
                            </span>
                            <Badge
                                v-if="index === activeIndex"
                                class="border-warning/30 bg-warning/20 text-xs text-warning"
                            >
                                {{ $t('milestones.next_up') }}
                            </Badge>
                        </div>

                        <div
                            class="flex items-center gap-3 text-sm text-muted-foreground"
                        >
                            <span
                                v-if="milestone.target_value"
                                class="flex items-center gap-1"
                            >
                                <Target class="size-3.5" />
                                {{ milestone.target_value.toLocaleString() }}
                                {{ record.unit }}
                            </span>
                            <span
                                v-if="milestone.deadline"
                                class="flex items-center gap-1"
                                :class="{
                                    'font-semibold text-warning':
                                        deadlineState(milestone) === 'due',
                                    'font-semibold text-destructive':
                                        deadlineState(milestone) === 'overdue',
                                }"
                            >
                                <Calendar class="size-3.5" />
                                {{
                                    $t('milestones.due_on', {
                                        date: moment(milestone.deadline).format(
                                            'L',
                                        ),
                                    })
                                }}
                            </span>
                        </div>

                        <div
                            v-if="
                                isAutoComplete(milestone) &&
                                !isCompleted(milestone)
                            "
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{
                                $t('milestones.auto_completes', {
                                    value:
                                        milestone.target_value?.toLocaleString() ??
                                        '',
                                    unit: record.unit ?? '',
                                    percent: Math.round(
                                        getProgress(milestone),
                                    ).toString(),
                                })
                            }}
                        </div>

                        <div
                            v-if="
                                isCompleted(milestone) && milestone.completed_at
                            "
                            class="text-xs text-success"
                        >
                            {{
                                $t('milestones.completed_on', {
                                    date: formatDate(milestone.completed_at),
                                })
                            }}
                        </div>
                    </div>

                    <div
                        v-if="isReorderable"
                        class="flex items-start gap-1 focus-within:opacity-100 sm:opacity-0 sm:group-hover/step:opacity-100"
                    >
                        <MilestoneFormModal
                            :goal_id="record.id"
                            :goal_type="record.type"
                            :position="index + 2"
                        >
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex size-8 cursor-pointer items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="$t('steps.insert_after')"
                                    :title="$t('steps.insert_after')"
                                >
                                    <Plus class="size-4" />
                                </button>
                            </template>
                        </MilestoneFormModal>
                        <button
                            type="button"
                            :data-drag-handle="milestone.id"
                            class="flex size-8 cursor-grab touch-none items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground active:cursor-grabbing"
                            :aria-label="
                                $t('steps.move_handle', {
                                    title: milestone.title,
                                })
                            "
                            :title="$t('steps.move_handle_hint')"
                            @keydown.up.prevent="
                                moveWithKeyboard(index, index - 1)
                            "
                            @keydown.down.prevent="
                                moveWithKeyboard(index, index + 1)
                            "
                        >
                            <GripVertical class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add milestone button at the end -->
            <div class="flex items-center gap-4">
                <MilestoneFormModal
                    :goal_id="props.record.id"
                    :goal_type="props.record.type"
                >
                    <template #trigger>
                        <button
                            class="flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-dashed border-border transition-colors hover:border-muted-foreground"
                        >
                            <Plus class="size-4 text-muted-foreground" />
                        </button>
                    </template>
                </MilestoneFormModal>
                <span class="text-sm text-muted-foreground">{{
                    $t(`${labelNamespace}.add`)
                }}</span>
            </div>
        </div>
    </div>
</template>
