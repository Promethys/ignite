import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MultiStepGoalCard from '@/components/goals/MultiStepGoalCard.vue';
import type { Goal } from '@/types/models';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Write a book',
    description: 'Write and publish a book',
    icon: '✍️',
    type: 'multi_step',
    direction: 'ascending',
    target_value: null,
    initial_value: 0,
    current_value: 0,
    unit: null,
    recurrence: null,
    start_date: '2026-01-01',
    deadline: null,
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    points: 200,
    is_public: false,
    order: 1,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
    progress_percentage: 0,
    is_overdue: false,
    is_completed: false,
};

const stubs = {
    Link: { template: '<div><slot /></div>' },
    Card: { template: '<div><slot /></div>' },
    GoalBadges: { template: '<div />' },
    Button: { template: '<button><slot /></button>' },
    DropdownMenu: { template: '<div><slot /></div>' },
    DropdownMenuTrigger: { template: '<div><slot /></div>' },
    DropdownMenuContent: { template: '<div><slot /></div>' },
    DropdownMenuGroup: { template: '<div><slot /></div>' },
    DropdownMenuItem: { template: '<div><slot /></div>' },
    AlertDialog: { template: '<div><slot /></div>' },
    AlertDialogTrigger: { template: '<div><slot /></div>' },
    AlertDialogContent: { template: '<div><slot /></div>' },
    AlertDialogHeader: { template: '<div><slot /></div>' },
    AlertDialogTitle: { template: '<div><slot /></div>' },
    AlertDialogDescription: { template: '<div><slot /></div>' },
    AlertDialogFooter: { template: '<div><slot /></div>' },
    AlertDialogCancel: { template: '<div><slot /></div>' },
    AlertDialogAction: { template: '<div><slot /></div>' },
};

describe('MultiStepGoalCard', () => {
    it('shows pause item when in_progress', () => {
        const wrapper = mount(MultiStepGoalCard, {
            props: { item: { ...baseGoal, status: 'in_progress' } },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Pause');
        expect(wrapper.text()).not.toContain('Resume');
    });

    it('shows resume item when paused', () => {
        const wrapper = mount(MultiStepGoalCard, {
            props: { item: { ...baseGoal, status: 'paused' } },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Resume');
        expect(wrapper.text()).not.toContain('Pause');
    });
});
