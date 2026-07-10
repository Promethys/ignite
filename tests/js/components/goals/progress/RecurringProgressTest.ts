import RecurringProgress from '@/components/goals/progress/RecurringProgress.vue';
import type { Goal, StreakData } from '@/types/models';
import { Flame } from 'lucide-vue-next';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Meditate daily',
    description: null,
    icon: null,
    type: 'recurring',
    direction: 'ascending',
    target_value: null,
    initial_value: 0,
    current_value: 0,
    unit: null,
    recurrence: 'daily',
    start_date: null,
    deadline: null,
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    polarity: 'positive',
    points: 0,
    is_public: false,
    order: 0,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
    progress_percentage: 0,
    is_overdue: false,
    is_completed: false,
};

const mountProgress = (item: Goal) => mount(RecurringProgress, { props: { item } });

describe('RecurringProgress', () => {
    it('renders the current streak with the unit noun', () => {
        const streak: StreakData = {
            current: 3,
            longest: 5,
            unit: 'day',
            current_period_satisfied: true,
        };

        const wrapper = mountProgress({ ...baseGoal, streak });

        expect(wrapper.text()).toContain('goals.streak.positive.day');
        expect(wrapper.text()).toContain('3');
    });

    it('derives the noun from the streak unit for other cadences', () => {
        const streak: StreakData = {
            current: 3,
            longest: 3,
            unit: 'week',
            current_period_satisfied: true,
        };

        const wrapper = mountProgress({ ...baseGoal, recurrence: 'weekly', streak });

        expect(wrapper.text()).toContain('goals.streak.positive.week');
    });

    it('renders an empty state when no streak is provided', () => {
        const wrapper = mountProgress({ ...baseGoal, streak: undefined });

        expect(wrapper.text()).toContain('goals.streak.none');
    });

    it('renders an empty state when the streak count is zero', () => {
        const streak: StreakData = {
            current: 0,
            longest: 0,
            unit: 'day',
            current_period_satisfied: false,
        };

        const wrapper = mountProgress({ ...baseGoal, streak });

        expect(wrapper.text()).toContain('goals.streak.none');
    });

    it('renders a flame icon', () => {
        const streak: StreakData = {
            current: 3,
            longest: 5,
            unit: 'day',
            current_period_satisfied: true,
        };

        const wrapper = mountProgress({ ...baseGoal, streak });

        expect(wrapper.findComponent(Flame).exists()).toBe(true);
    });

    it('renders avoidance framing for a negative streak', () => {
        const streak: StreakData = {
            current: 27,
            longest: 27,
            unit: 'day',
            current_period_satisfied: true,
        };

        const wrapper = mountProgress({
            ...baseGoal,
            polarity: 'negative',
            streak,
        });

        expect(wrapper.text()).toContain('27');
        expect(wrapper.text()).toContain('goals.streak.negative.day');
    });

    it('pluralizes the unit noun for negative streaks', () => {
        const streak: StreakData = {
            current: 27,
            longest: 27,
            unit: 'day',
            current_period_satisfied: true,
        };

        const wrapper = mountProgress({
            ...baseGoal,
            polarity: 'negative',
            streak,
        });

        expect(wrapper.text()).toContain('27');
        expect(wrapper.text()).toContain('goals.streak.negative.day');
    });

    it('renders the French avoidance phrase when $t maps the key', () => {
        const streak: StreakData = {
            current: 27,
            longest: 27,
            unit: 'day',
            current_period_satisfied: true,
        };

        const wrapper = mount(RecurringProgress, {
            props: { item: { ...baseGoal, polarity: 'negative', streak } },
            global: {
                mocks: {
                    $tChoice: () => '27 jours sans rechute',
                },
            },
        });

        expect(wrapper.text()).toContain('27 jours sans rechute');
    });
});
