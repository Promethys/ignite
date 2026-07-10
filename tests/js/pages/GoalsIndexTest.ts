import GoalsIndex from '@/pages/Goals/Index.vue';
import type { Category, Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// The Inertia <Head> component needs the app's head manager, which isn't set
// up in unit tests; stub only that export and keep the rest of the module real.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return { ...actual, Head: { name: 'Head', render: () => null } };
});

let nextId = 1;
const makeGoal = (overrides: Partial<Goal>): Goal =>
    ({
        id: nextId++,
        user_id: 1,
        category_id: null,
        title: 'Goal',
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
        ...overrides,
    }) as Goal;

const goalsData = [
    makeGoal({
        title: 'Run a marathon',
        status: 'in_progress',
        category_id: 1,
    }),
    makeGoal({ title: 'Read books', status: 'completed', category_id: 2 }),
    makeGoal({ title: 'Save money', status: 'paused', category_id: 1 }),
];

const categories = [
    { id: 1, name: 'Health' },
    { id: 2, name: 'Learning' },
] as Category[];

const stubs = {
    AppLayout: { template: '<div><slot /></div>' },
    PageHeader: { template: '<div><slot name="actions" /></div>' },
    Link: { template: '<a><slot /></a>' },
    Button: { template: '<button><slot /></button>' },
    Input: {
        template:
            '<input class="search" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
    Select: { template: '<div><slot /></div>' },
    SelectTrigger: { template: '<div><slot /></div>' },
    SelectContent: { template: '<div><slot /></div>' },
    SelectItem: { template: '<div><slot /></div>' },
    SelectValue: { template: '<div />' },
    Separator: { template: '<div />' },
    Empty: { template: '<div class="empty"><slot /></div>' },
    EmptyHeader: { template: '<div><slot /></div>' },
    EmptyMedia: { template: '<div><slot /></div>' },
    EmptyTitle: { template: '<div><slot /></div>' },
    EmptyDescription: { template: '<div><slot /></div>' },
    EmptyContent: { template: '<div><slot /></div>' },
    GoalCard: {
        props: ['item'],
        template: '<div class="goal-card">{{ item.title }}</div>',
    },
};

const mountIndex = () =>
    mount(GoalsIndex, {
        props: { items: goalsData, categories },
        global: { stubs },
    });

describe('Goals/Index filtering', () => {
    it('renders every goal by default', () => {
        const wrapper = mountIndex();
        expect(wrapper.findAll('.goal-card')).toHaveLength(3);
    });

    it('filters by status pill', async () => {
        const wrapper = mountIndex();
        const completed = wrapper
            .findAll('button')
            .find((b) => b.text() === 'goals.statuses.completed');
        await completed!.trigger('click');

        const cards = wrapper.findAll('.goal-card');
        expect(cards).toHaveLength(1);
        expect(cards[0].text()).toBe('Read books');
    });

    it('filters by search query', async () => {
        const wrapper = mountIndex();
        await wrapper.find('input.search').setValue('marathon');

        const cards = wrapper.findAll('.goal-card');
        expect(cards).toHaveLength(1);
        expect(cards[0].text()).toBe('Run a marathon');
    });

    it('shows the empty state when no goal matches', async () => {
        const wrapper = mountIndex();
        await wrapper.find('input.search').setValue('nonexistent goal name');

        expect(wrapper.findAll('.goal-card')).toHaveLength(0);
        expect(wrapper.find('.empty').exists()).toBe(true);
    });
});
