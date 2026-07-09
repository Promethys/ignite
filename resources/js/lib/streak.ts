import type { Goal } from '@/types/models';

export const unitByRecurrence: Record<
    NonNullable<Goal['recurrence']>,
    string
> = {
    daily: 'day',
    weekly: 'week',
    monthly: 'month',
    annually: 'year',
};

export function streakUnit(goal: Pick<Goal, 'streak' | 'recurrence'>): string {
    return (
        goal.streak?.unit ??
        unitByRecurrence[goal.recurrence ?? 'daily'] ??
        'day'
    );
}

export function pluralizeUnit(unit: string, count: number): string {
    return count === 1 ? unit : `${unit}s`;
}
