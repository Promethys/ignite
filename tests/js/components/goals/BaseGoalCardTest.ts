import GoalCard from '@/components/goals/GoalCard.vue';
import type { Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Read 12 books',
    description: 'A year of reading',
    icon: null,
    type: 'quantifiable',
    direction: 'ascending',
    target_value: 12,
    initial_value: 0,
    current_value: 3,
    unit: 'books',
    recurrence: null,
    start_date: '2026-01-01',
    deadline: null,
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    points: 0,
    is_public: false,
    order: 1,
    created_at: '2026-01-01',
    updated_at: '2026-01-01',
    progress_percentage: 25,
    is_overdue: false,
    is_completed: false,
    category: { name: 'Learning' } as Goal['category'],
    milestones: [],
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

const mountCard = (item: Goal) =>
    mount(GoalCard, { props: { item }, global: { stubs } });

describe('GoalCard', () => {
    it('renders title and description', () => {
        const wrapper = mountCard(baseGoal);

        expect(wrapper.text()).toContain('Read 12 books');
        expect(wrapper.text()).toContain('A year of reading');
    });

    it('renders a quantifiable progress bar with percent and totals', () => {
        const wrapper = mountCard(baseGoal);

        expect(wrapper.text()).toContain('25%');
        expect(wrapper.text()).toContain('3');
        expect(wrapper.text()).toContain('12 books');
        expect(wrapper.find('.progress').exists()).toBe(true);
    });

    it('renders no progress zone for simple goals', () => {
        const wrapper = mountCard({ ...baseGoal, type: 'simple' });

        expect(wrapper.find('.progress').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('%');
    });

    it('applies the success tint when completed, without a strikethrough', () => {
        const wrapper = mountCard({
            ...baseGoal,
            status: 'completed',
            completed_at: '2026-06-01',
        });

        expect(wrapper.html()).toContain('bg-success-subtle');
        expect(wrapper.html()).not.toContain('line-through');
    });

    it('shows X / Y steps for multi-step goals', () => {
        const wrapper = mountCard({
            ...baseGoal,
            type: 'multi_step',
            milestones: [
                { id: 1, is_completed: true },
                { id: 2, is_completed: false },
            ] as Goal['milestones'],
        });

        expect(wrapper.text()).toContain('1 / 2 steps');
    });

    it('shows a streak scaffold for recurring goals', () => {
        const wrapper = mountCard({
            ...baseGoal,
            type: 'recurring',
            recurrence: 'daily',
        });

        expect(wrapper.text()).toContain('No streak yet');
    });
});
