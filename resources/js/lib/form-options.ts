// Labels are translation keys resolved with `$t()` where the options render.

export function getGoalTypeOptions() {
    return [
        { value: 'simple', label: 'goals.types.simple' },
        { value: 'quantifiable', label: 'goals.types.quantifiable' },
        { value: 'recurring', label: 'goals.types.recurring' },
        { value: 'multi_step', label: 'goals.types.multi_step' },
    ];
}

export function getGoalPriorityOptions() {
    return [
        { value: 'low', label: 'goals.priorities.low' },
        { value: 'medium', label: 'goals.priorities.medium' },
        { value: 'high', label: 'goals.priorities.high' },
    ];
}

export function getGoalPolarityOptions() {
    return [
        { value: 'positive', label: 'goals.polarities.positive' },
        { value: 'negative', label: 'goals.polarities.negative' },
    ];
}

export function getGoalStatusOptions() {
    return [
        { value: 'not_started', label: 'goals.statuses.not_started' },
        { value: 'in_progress', label: 'goals.statuses.in_progress' },
        { value: 'completed', label: 'goals.statuses.completed' },
        { value: 'paused', label: 'goals.statuses.paused' },
        { value: 'abandoned', label: 'goals.statuses.abandoned' },
    ];
}

export function getGoalRecurrenceOptions() {
    return [
        { value: 'daily', label: 'goals.recurrences.daily' },
        { value: 'weekly', label: 'goals.recurrences.weekly' },
        { value: 'monthly', label: 'goals.recurrences.monthly' },
        { value: 'annually', label: 'goals.recurrences.annually' },
    ];
}

export function getGoalDirectionOptions() {
    return [
        { value: 'ascending', label: 'goals.directions.ascending' },
        { value: 'descending', label: 'goals.directions.descending' },
    ];
}

export function getMilestoneViewOptions() {
    return [
        {
            value: 'timeline',
            label: 'Timeline',
            description: 'Vertical stepper with progress line',
        },
        {
            value: 'checklist',
            label: 'Checklist',
            description: 'Simple checkbox list',
        },
        {
            value: 'cards',
            label: 'Cards Grid',
            description: 'Card layout like Categories',
        },
        {
            value: 'track',
            label: 'Progress Track',
            description: 'Markers on progress bar',
        },
    ];
}
