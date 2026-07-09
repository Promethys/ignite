export function getGoalTypeOptions() {
    return [
        { value: 'simple', label: 'Simple' },
        { value: 'quantifiable', label: 'Quantifiable' },
        { value: 'recurring', label: 'Recurring' },
        { value: 'multi_step', label: 'Multi Step' },
    ];
}

export function getGoalPriorityOptions() {
    return [
        { value: 'low', label: 'Low' },
        { value: 'medium', label: 'Medium' },
        { value: 'high', label: 'High' },
    ];
}

export function getGoalPolarityOptions() {
    return [
        { value: 'positive', label: 'Positive' },
        { value: 'negative', label: 'Negative' },
    ];
}

export function getGoalStatusOptions() {
    return [
        { value: 'not_started', label: 'Not Started' },
        { value: 'in_progress', label: 'In Progress' },
        { value: 'completed', label: 'Completed' },
        { value: 'paused', label: 'Paused' },
        { value: 'abandoned', label: 'Abandoned' },
    ];
}

export function getGoalRecurrenceOptions() {
    return [
        { value: 'daily', label: 'Daily' },
        { value: 'weekly', label: 'Weekly' },
        { value: 'monthly', label: 'Monthly' },
        { value: 'annually', label: 'Annually' },
    ];
}

export function getGoalDirectionOptions() {
    return [
        { value: 'ascending', label: 'Ascending' },
        { value: 'descending', label: 'Descending' },
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
