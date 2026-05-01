import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import QuantifiableGoalCard from '@/components/goals/QuantifiableGoalCard.vue';
import type { Goal } from '@/types/models';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Read 50 books',
    description: 'Read 50 books this year',
    icon: '📚',
    type: 'quantifiable',
    direction: 'ascending',
    target_value: 50,
    initial_value: 0,
    current_value: 25,
    unit: 'books',
    recurrence: null,
    start_date: '2026-01-01',
    deadline: null,
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    points: 100,
    is_public: false,
    order: 1,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
    progress_percentage: 50,
    is_overdue: false,
    is_completed: false,
};

const stubs = {
    Link: { template: '<div><slot /></div>' },
    Card: { name: 'Card', template: '<div><slot /></div>' },
    Progress: { template: '<div class="progress" />', props: ['modelValue'] },
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

describe('QuantifiableGoalCard', () => {
    it('renders goal title and description', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Read 50 books');
        expect(wrapper.text()).toContain('Read 50 books this year');
    });

    it('shows progress bar with correct percentage', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('50%');
        expect(wrapper.text()).toContain('25');
        expect(wrapper.text()).toContain('50');
        expect(wrapper.text()).toContain('books');
    });

    it('applies strikethrough and opacity when goal is completed', () => {
        const completedGoal: Goal = {
            ...baseGoal,
            status: 'completed',
            completed_at: '2026-03-01',
            is_completed: true,
        };

        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: completedGoal },
            global: { stubs },
        });

        const title = wrapper.find('h3');
        expect(title.classes()).toContain('line-through');

        const card = wrapper.findComponent({ name: 'Card' });
        expect(card.classes()).toContain('opacity-60');
    });

    it('shows pause item only when goal is in_progress', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'in_progress' } },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Pause');
        expect(wrapper.text()).not.toContain('Resume');
    });

    it('shows resume item only when goal is paused', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'paused' } },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Resume');
        expect(wrapper.text()).not.toContain('Pause');
    });

    it('shows deadline with urgent styling when overdue', () => {
        const today = new Date().toISOString().split('T')[0];
        const goalWithDeadline: Goal = {
            ...baseGoal,
            deadline: today,
        };

        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: goalWithDeadline },
            global: { stubs },
        });

        const deadlineSpan = wrapper.find('span.text-orange-400');
        expect(deadlineSpan.exists()).toBe(true);
    });

    it('shows deadline with destructive styling when past due', () => {
        const past = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        const goalPastDue: Goal = {
            ...baseGoal,
            deadline: past,
        };

        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: goalPastDue },
            global: { stubs },
        });

        const deadlineSpan = wrapper.find('span.text-destructive');
        expect(deadlineSpan.exists()).toBe(true);
    });
});
