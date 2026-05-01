import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import GoalBadges from '@/components/goals/GoalBadges.vue';
import type { Goal } from '@/types/models';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Test Goal',
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
    points: 0,
    is_public: false,
    order: 0,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
    progress_percentage: 0,
    is_overdue: false,
    is_completed: false,
};

const BadgeStub = {
    template: '<span><slot /></span>',
};

describe('GoalBadges', () => {
    it('shows correct badge for each goal type', () => {
        const recurringGoal: Goal = {
            ...baseGoal,
            type: 'recurring',
            recurrence: 'daily',
        };

        const wrapper = mount(GoalBadges, {
            props: { goal: recurringGoal },
            global: { stubs: { Badge: BadgeStub } },
        });

        expect(wrapper.text()).toContain('Daily');
    });

    it('shows status badge', () => {
        const wrapper = mount(GoalBadges, {
            props: { goal: { ...baseGoal, status: 'in_progress' } },
            global: { stubs: { Badge: BadgeStub } },
        });

        expect(wrapper.text()).toContain('In Progress');
    });

    it('shows priority badge when set', () => {
        const wrapper = mount(GoalBadges, {
            props: { goal: { ...baseGoal, priority: 'high' } },
            global: { stubs: { Badge: BadgeStub } },
        });

        expect(wrapper.text()).toContain('High Priority');
    });
});
