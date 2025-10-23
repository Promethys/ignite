export function getGoalTypeOptions() {
    return [
        {value: 'simple', label: 'Simple'}, 
        {value: 'quantifiable', label: 'Quantifiable'}, 
        {value: 'recurring', label: 'Recurring'}, 
        {value: 'multi_step', label: 'Multi Step'} 
    ];
}

export function getGoalPriorityOptions() {
    return [
        {value: 'low', label: 'Low'}, 
        {value: 'medium', label: 'Medium'}, 
        {value: 'high', label: 'High'}, 
    ];
}

export function getGoalStatusOptions() {
    return [
        {value: 'not_started', label: 'Not Started'}, 
        {value: 'in_progress', label: 'In Progress'}, 
        {value: 'completed', label: 'Completed'}, 
        {value: 'paused', label: 'Paused'}, 
        {value: 'abandoned', label: 'Abandoned'}, 
    ];
}

export function getGoalRecurrenceOptions() {
    return [
        {value: 'daily', label: 'Daily'}, 
        {value: 'weekly', label: 'Weekly'}, 
        {value: 'monthly', label: 'Monthly'}, 
    ];
}
