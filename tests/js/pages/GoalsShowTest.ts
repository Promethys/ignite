import GoalsShow from '@/pages/Goals/Show.vue';
import type { Goal, GoalEntry } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// <Head> needs the app head manager, absent in unit tests; stub only that export.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return { ...actual, Head: { name: 'Head', render: () => null } };
});

const passthrough = (names: string[]) =>
    Object.fromEntries(
        names.map((n) => [n, { template: '<div><slot /></div>' }]),
    );

const stubs = {
    ...passthrough([
        'AppLayout',
        'GoalBadges',
        'ProgressChart',
        'Timeline',
        'MilestoneFormModal',
        'Dialog',
        'DialogTrigger',
        'DialogContent',
        'DialogHeader',
        'DialogTitle',
        'DialogDescription',
        'DialogFooter',
        'DialogClose',
        'DropdownMenu',
        'DropdownMenuTrigger',
        'DropdownMenuContent',
        'DropdownMenuGroup',
        'DropdownMenuItem',
        'AlertDialog',
        'AlertDialogTrigger',
        'AlertDialogContent',
        'AlertDialogHeader',
        'AlertDialogTitle',
        'AlertDialogDescription',
        'AlertDialogFooter',
        'AlertDialogCancel',
        'AlertDialogAction',
        'Input',
        'Label',
        'Textarea',
        'InputError',
    ]),
    PageHeader: {
        props: ['title', 'description'],
        template:
            '<div><h1>{{ title }}</h1><p>{{ description }}</p><slot name="actions" /></div>',
    },
    Link: { template: '<a><slot /></a>' },
    Button: { template: '<button><slot /></button>' },
};

let entryId = 1;
const makeEntry = (value: number, increment: number): GoalEntry =>
    ({
        id: entryId++,
        goal_id: 1,
        value,
        previous_value: value - increment,
        increment_value: increment,
        note: null,
        entry_date: '2026-03-01',
        attachment_path: null,
        attachment_type: null,
        created_at: '2026-03-01',
        updated_at: '2026-03-01',
    }) as GoalEntry;

const makeGoal = (overrides: Partial<Goal>): Goal =>
    ({
        id: 1,
        user_id: 1,
        category_id: null,
        title: 'Run a marathon',
        description: 'Train consistently',
        icon: null,
        type: 'quantifiable',
        direction: 'ascending',
        target_value: 42,
        initial_value: 0,
        current_value: 26,
        unit: 'km',
        recurrence: null,
        start_date: '2026-01-01',
        deadline: null,
        completed_at: null,
        status: 'in_progress',
        priority: 'high',
        points: 0,
        is_public: false,
        order: 0,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
        progress_percentage: 62,
        is_overdue: false,
        is_completed: false,
        milestones: [],
        entries: [],
        ...overrides,
    }) as Goal;

const mountShow = (
    goal: Goal,
    chartEntries: { entry_date: string; value: number }[] = [],
) => mount(GoalsShow, { props: { goal, chartEntries }, global: { stubs } });

describe('Goals/Show', () => {
    it('renders quantifiable summary tiles', () => {
        const wrapper = mountShow(makeGoal({}), [
            { entry_date: '2026-02-01', value: 20 },
            { entry_date: '2026-03-01', value: 26 },
        ]);

        expect(wrapper.text()).toContain('62%'); // progress
        expect(wrapper.text()).toContain('26 / 42'); // current / target
        expect(wrapper.text()).toContain('Entries logged');
    });

    it('pluralizes the "view all entries" link', () => {
        const many = mountShow(makeGoal({ entries: [makeEntry(26, 4)] }), [
            { entry_date: '2026-02-01', value: 20 },
            { entry_date: '2026-03-01', value: 26 },
        ]);
        expect(many.text()).toContain('View all 2 entries');

        const one = mountShow(makeGoal({ entries: [makeEntry(26, 4)] }), [
            { entry_date: '2026-03-01', value: 26 },
        ]);
        expect(one.text()).toContain('View all 1 entry');
    });

    it('labels the deadline tile as overdue when the deadline has passed', () => {
        const wrapper = mountShow(makeGoal({ deadline: '2020-01-01' }));
        expect(wrapper.text()).toContain('Overdue');
    });

    it('shows the real status for a simple goal, not a hardcoded one', () => {
        const wrapper = mountShow(
            makeGoal({ type: 'simple', status: 'not_started' }),
        );
        expect(wrapper.text()).toContain('Not Started');
        expect(wrapper.text()).not.toContain('In progress');
    });

    it('shows steps completed for a multi-step goal', () => {
        const wrapper = mountShow(
            makeGoal({
                type: 'multi_step',
                milestones: [
                    { id: 1, is_completed: true },
                    { id: 2, is_completed: false },
                    { id: 3, is_completed: false },
                ] as Goal['milestones'],
            }),
        );
        expect(wrapper.text()).toContain('1 / 3');
    });

    it('shows current and longest streak for a recurring goal', () => {
        const wrapper = mountShow(
            makeGoal({
                type: 'recurring',
                recurrence: 'daily',
                streak: {
                    current: 3,
                    longest: 5,
                    unit: 'day',
                    current_period_satisfied: true,
                },
            }),
        );

        expect(wrapper.text()).toContain('3-day streak');
        expect(wrapper.text()).toContain('Longest: 5-day streak');
    });

    it('shows an empty streak state for a recurring goal with no streak', () => {
        const wrapper = mountShow(
            makeGoal({ type: 'recurring', recurrence: 'daily' }),
        );

        expect(wrapper.text()).toContain('No active streak');
    });
});
