import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SimpleGoalCard from '@/components/goals/SimpleGoalCard.vue';
import type { Goal } from '@/types/models';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Learn guitar',
    description: 'Learn to play guitar',
    icon: '🎸',
    type: 'simple',
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
    points: 50,
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
    Card: { name: 'Card', template: '<div><slot /></div>' },
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

describe('SimpleGoalCard', () => {
    it('renders goal title', () => {
        const wrapper = mount(SimpleGoalCard, {
            props: { item: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Learn guitar');
    });

    it('applies completed styles when status is completed', () => {
        const completedGoal: Goal = {
            ...baseGoal,
            status: 'completed',
            completed_at: '2026-03-01',
            is_completed: true,
        };

        const wrapper = mount(SimpleGoalCard, {
            props: { item: completedGoal },
            global: { stubs },
        });

        const title = wrapper.find('h3');
        expect(title.classes()).toContain('line-through');

        const card = wrapper.findComponent({ name: 'Card' });
        expect(card.classes()).toContain('opacity-60');
    });

    it('shows pause and resume items correctly', () => {
        const pausedWrapper = mount(SimpleGoalCard, {
            props: { item: { ...baseGoal, status: 'paused' } },
            global: { stubs },
        });
        expect(pausedWrapper.text()).toContain('Resume');
        expect(pausedWrapper.text()).not.toContain('Pause');

        const inProgressWrapper = mount(SimpleGoalCard, {
            props: { item: { ...baseGoal, status: 'in_progress' } },
            global: { stubs },
        });
        expect(inProgressWrapper.text()).toContain('Pause');
        expect(inProgressWrapper.text()).not.toContain('Resume');
    });
});
