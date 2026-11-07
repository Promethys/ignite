import type { HeatmapPayload } from '@/lib/heatmap';
import type { Goal } from '@/types/models';
import type { UIMessage } from 'ai';
import type { trans } from 'laravel-vue-i18n';

export const showcaseImageDimensions = {
    assistant: { width: 1440, height: 900 },
    themes: { width: 624, height: 435 },
};

const today = new Date();
const relativeDate = (daysAgo: number) => {
    const day = new Date(today);
    day.setDate(day.getDate() - daysAgo);
    return `${day.getFullYear()}-${String(day.getMonth() + 1).padStart(2, '0')}-${String(day.getDate()).padStart(2, '0')}`;
};
const date = relativeDate(0);
const base: Goal = {
    id: 12,
    user_id: 0,
    category_id: null,
    title: '',
    description: null,
    icon: null,
    type: 'simple',
    direction: 'ascending',
    target_value: null,
    initial_value: 0,
    current_value: 0,
    unit: null,
    recurrence: null,
    start_date: null,
    deadline: null,
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    polarity: 'positive',
    points: 0,
    is_public: false,
    order: 0,
    created_at: date,
    updated_at: date,
    progress_percentage: 0,
    is_completed: false,
};

export const simpleGoal: Goal = {
    ...base,
    type: 'simple',
    status: 'completed',
    is_completed: true,
    progress_percentage: 100,
    completed_at: date,
};
const currentValue = 259;
const targetValue = 400;

export const quantifiableGoal: Goal = {
    ...base,
    type: 'quantifiable',
    target_value: targetValue,
    current_value: currentValue,
    unit: 'km',
    progress_percentage: Math.floor((currentValue / targetValue) * 100),
};
export const recurringGoal: Goal = {
    ...base,
    type: 'recurring',
    recurrence: 'daily',
    streak: {
        current: 10,
        longest: 12,
        unit: 'day',
        current_period_satisfied: true,
    },
};
export const multiStepGoal: Goal = {
    ...base,
    type: 'multi_step',
    progress_percentage: 66.67,
    milestones: Array.from({ length: 3 }, (_, index) => ({
        id: index + 1,
        goal_id: 12,
        title: `public.showcase.step_${index + 1}`,
        description: null,
        target_value: null,
        deadline: null,
        order: index,
        is_completed: index < 2,
        is_reached: false,
        completed_at: index < 2 ? date : null,
        points_reward: null,
        created_at: date,
        updated_at: date,
    })),
};

const cells = Array.from({ length: 112 }, (_, index) => {
    return {
        date: relativeDate(111 - index),
        value:
            index >= 102 ||
            (index >= 88 && index < 100) ||
            (index < 88 && index % 5 !== 0)
                ? 1
                : 0,
    };
});
export const heatmap: HeatmapPayload = {
    cadence: 'daily',
    cells,
    total: cells.filter((cell) => cell.value > 0).length,
};
export const progressEntries: Pick<
    NonNullable<Goal['entries']>[number],
    'entry_date' | 'value'
>[] = Array.from({ length: 12 }, (_, index) => ({
    entry_date: relativeDate((11 - index) * 9),
    value: [0, 12, 36, 51, 80, 96, 120, 152, 177, 205, 235, 259][index],
}));

export const assistantMessages = (translate: typeof trans): UIMessage[] => [
    {
        id: 'showcase-user',
        role: 'user',
        parts: [
            {
                type: 'text',
                text: translate('public.showcase.assistant_prompt'),
            },
        ],
    },
    {
        id: 'showcase-assistant',
        role: 'assistant',
        parts: [
            {
                type: 'tool-log_progress',
                toolCallId: 'showcase-log-progress',
                state: 'output-available',
                input: { goal_id: quantifiableGoal.id, increment: 5 },
                output: {
                    current_value: currentValue + 5,
                    target_value: targetValue,
                },
            },
            {
                type: 'text',
                text: translate('public.showcase.assistant_reply', {
                    current: String(currentValue + 5),
                    target: String(targetValue),
                }),
            },
            {
                type: 'tool-delete_goal',
                toolCallId: 'showcase-delete-goal',
                state: 'approval-requested',
                input: { goal_id: 13 },
                approval: { id: 'showcase-delete-10k' },
            },
        ],
    },
];
