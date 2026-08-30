import GoalsIndex from '@/pages/Goals/Index.vue';
import type { Category, Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

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
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    Button: {
        props: ['variant'],
        template: '<button :data-variant="variant"><slot /></button>',
    },
    Input: {
        props: ['modelValue'],
        template:
            '<input class="search" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
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

const mountIndex = (items: Goal[] = goalsData) =>
    mount(GoalsIndex, {
        props: { items, categories },
        global: { stubs },
    });

const findCreateLink = (wrapper: ReturnType<typeof mountIndex>) =>
    wrapper.findAll('a').find((a) => a.text().includes('goals.actions.new'));

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

describe('Goals/Index new goal link', () => {
    afterEach(() => window.history.replaceState({}, '', '/'));

    it('carries the selected category into the create link', () => {
        window.history.replaceState({}, '', '/goals?category=1');

        const wrapper = mountIndex();

        const createLink = findCreateLink(wrapper);
        expect(createLink).toBeTruthy();
        expect(createLink!.attributes('href')).toContain('?category=1');
    });

    it('has no category query when the filter is set to all', () => {
        const wrapper = mountIndex();

        const createLink = findCreateLink(wrapper);
        expect(createLink).toBeTruthy();
        expect(createLink!.attributes('href')).not.toContain('category=');
    });
});

describe('Goals/Index url filters', () => {
    const setUrl = (url: string) => window.history.replaceState({}, '', url);

    const flushUrlWrite = async () => {
        await nextTick();
        vi.advanceTimersByTime(300);
        await nextTick();
    };

    afterEach(() => {
        vi.useRealTimers();
        setUrl('/');
    });

    it('seeds the status and search from the query string', () => {
        setUrl('/goals?status=paused&search=save');

        const wrapper = mountIndex();

        const cards = wrapper.findAll('.goal-card');
        expect(cards).toHaveLength(1);
        expect(cards[0].text()).toBe('Save money');
        expect(wrapper.find('input.search').element.value).toBe('save');

        expect(wrapper.find('[data-variant="default"]').text()).toBe(
            'goals.statuses.paused',
        );
        expect(wrapper.findAll('[data-variant="outline"]')).toHaveLength(4);
    });

    it('seeds the category from the query string', () => {
        setUrl('/goals?category=1');

        const wrapper = mountIndex();

        const cards = wrapper.findAll('.goal-card');
        expect(cards.map((card) => card.text())).toEqual([
            'Run a marathon',
            'Save money',
        ]);
    });

    it('treats the none sentinel as the uncategorised filter', () => {
        setUrl('/goals?category=none');

        const uncategorised = makeGoal({
            title: 'Walk the dog',
            category_id: null,
        });
        const wrapper = mountIndex([...goalsData, uncategorised]);

        const cards = wrapper.findAll('.goal-card');
        expect(cards).toHaveLength(1);
        expect(cards[0].text()).toBe('Walk the dog');
    });

    it('writes a changed filter to the url', async () => {
        vi.useFakeTimers();
        setUrl('/goals');

        const wrapper = mountIndex();
        const completed = wrapper
            .findAll('button')
            .find((b) => b.text() === 'goals.statuses.completed');
        await completed!.trigger('click');
        await flushUrlWrite();

        expect(window.location.search).toBe('?status=completed');
    });

    it('keeps the url clean on an untouched page', async () => {
        vi.useFakeTimers();
        setUrl('/goals');

        mountIndex();
        await flushUrlWrite();

        expect(window.location.search).toBe('');
    });
});
