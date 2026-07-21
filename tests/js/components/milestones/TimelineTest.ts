import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Timeline from '@/components/milestones/Timeline.vue';
import type { Goal, Milestone } from '@/types/models';

vi.mock('@/lib/utils', () => ({
    formatDate: (date: string) => `formatted:${date}`,
}));

vi.mock('lucide-vue-next', () => ({
    Check: { template: '<span class="icon-check" />' },
    Plus: { template: '<span class="icon-plus" />' },
    Target: { template: '<span class="icon-target" />' },
    Calendar: { template: '<span class="icon-calendar" />' },
    RotateCcw: { template: '<span class="icon-rotate" />' },
}));

const stubs = {
    Badge: { template: '<span class="badge"><slot /></span>' },
    MilestoneFormModal: {
        template: '<div class="milestone-form-modal"><slot /><slot name="trigger" /></div>',
    },
};

const makeMilestone = (overrides: Partial<Milestone> = {}): Milestone => ({
    id: 1,
    goal_id: 1,
    title: 'Milestone 1',
    description: null,
    target_value: null,
    order: 1,
    is_completed: false,
    is_reached: false,
    completed_at: null,
    points_reward: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
});

const makeGoal = (overrides: Partial<Goal> = {}): Goal => ({
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Test Goal',
    description: null,
    icon: null,
    type: 'quantifiable',
    direction: 'ascending',
    target_value: 1000,
    initial_value: 0,
    current_value: 0,
    unit: 'euros',
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
    milestones: [],
    ...overrides,
});

describe('Timeline', () => {
    // =========================================================================
    // EMPTY STATE
    // =========================================================================

    it('renders add milestone button when no milestones exist', () => {
        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones: [] }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.add');
    });

    it('renders milestone form modal', () => {
        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones: [] }) },
            global: { stubs },
        });

        expect(wrapper.find('.milestone-form-modal').exists()).toBe(true);
    });

    // =========================================================================
    // MILESTONE RENDERING
    // =========================================================================

    it('renders all milestone titles', () => {
        const milestones = [
            makeMilestone({ id: 1, title: 'First step' }),
            makeMilestone({ id: 2, title: 'Second step' }),
            makeMilestone({ id: 3, title: 'Final step' }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('First step');
        expect(wrapper.text()).toContain('Second step');
        expect(wrapper.text()).toContain('Final step');
    });

    // =========================================================================
    // COMPLETED MILESTONES
    // =========================================================================

    it('shows check icon for completed milestones', () => {
        const milestones = [
            makeMilestone({ id: 1, is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        expect(wrapper.find('.icon-check').exists()).toBe(true);
    });

    it('applies line-through class to completed milestone title', () => {
        const milestones = [
            makeMilestone({ id: 1, title: 'Done', is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        const titleSpan = wrapper.findAll('span').find((s) => s.text() === 'Done');
        expect(titleSpan?.classes()).toContain('line-through');
    });

    it('applies opacity-60 to completed milestone content', () => {
        const milestones = [
            makeMilestone({ id: 1, is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        const contentDiv = wrapper.find('.opacity-60');
        expect(contentDiv.exists()).toBe(true);
    });

    it('shows completed date for completed milestones with completed_at', () => {
        const milestones = [
            makeMilestone({ id: 1, is_completed: true, completed_at: '2026-03-15' }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.completed_on');
        expect(wrapper.text()).toContain('formatted:2026-03-15');
    });

    // =========================================================================
    // AUTO-COMPLETED MILESTONES
    // =========================================================================

    it('shows completed styling when a milestone is reached (is_reached)', () => {
        const milestones = [
            makeMilestone({
                id: 1,
                target_value: 500,
                is_completed: false,
                is_reached: true,
            }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 500, milestones }) },
            global: { stubs },
        });

        expect(wrapper.find('.icon-check').exists()).toBe(true);
        expect(wrapper.find('.opacity-60').exists()).toBe(true);
    });

    it('does not auto-complete milestone when current_value is below target_value', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 500, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 250, milestones }) },
            global: { stubs },
        });

        expect(wrapper.find('.icon-check').exists()).toBe(false);
        expect(wrapper.find('.opacity-60').exists()).toBe(false);
    });

    it('shows auto-complete info text for uncompleted auto-complete milestones', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 500, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 250, milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.auto_completes');
        expect(wrapper.text()).toContain('500');
    });

    it('does not show auto-complete info for completed milestones', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 500, is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 500, milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).not.toContain('milestones.auto_completes');
    });

    // =========================================================================
    // ACTIVE MILESTONE (NEXT UP)
    // =========================================================================

    it('shows Next up badge on first uncompleted milestone', () => {
        const milestones = [
            makeMilestone({ id: 1, is_completed: true }),
            makeMilestone({ id: 2, is_completed: false, target_value: 500 }),
            makeMilestone({ id: 3, is_completed: false, target_value: 800 }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 100, milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.next_up');
        const badges = wrapper.findAll('.badge');
        expect(badges).toHaveLength(1);
    });

    it('does not show Next up badge when all milestones are completed', () => {
        const milestones = [
            makeMilestone({ id: 1, is_completed: true }),
            makeMilestone({ id: 2, is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).not.toContain('Next up');
    });

    it('applies warning text color to active milestone title', () => {
        const milestones = [
            makeMilestone({ id: 1, title: 'Active', target_value: 500, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 100, milestones }) },
            global: { stubs },
        });

        const titleSpan = wrapper.findAll('span').find((s) => s.text() === 'Active');
        expect(titleSpan?.classes()).toContain('text-warning');
    });

    // =========================================================================
    // TARGET VALUE DISPLAY
    // =========================================================================

    it('displays target value with unit when milestone has target_value', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 250 }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones, unit: 'euros' }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('250');
        expect(wrapper.text()).toContain('euros');
    });

    it('does not display target value when milestone has no target_value', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: null }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        expect(wrapper.find('.icon-target').exists()).toBe(false);
    });

    // =========================================================================
    // PROGRESS CALCULATION
    // =========================================================================

    it('calculates progress percentage for auto-complete milestones', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 200, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 100, milestones }) },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.auto_completes');
        expect(wrapper.text()).toContain('50');
    });

    it('shows the check once a milestone is reached', () => {
        const milestones = [
            makeMilestone({
                id: 1,
                target_value: 100,
                is_completed: false,
                is_reached: true,
            }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 200, milestones }) },
            global: { stubs },
        });

        expect(wrapper.find('.icon-check').exists()).toBe(true);
    });

    // =========================================================================
    // TOGGLE AFFORDANCE
    // =========================================================================

    it('renders auto-complete milestones as non-interactive (cursor-default)', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: 500, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ current_value: 250, milestones }) },
            global: { stubs },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('cursor-default');
    });

    it('renders manual steps as interactive (cursor-pointer)', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: null, is_completed: false }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('cursor-pointer');
    });

    it('keeps completed manual steps interactive so they can be undone', () => {
        const milestones = [
            makeMilestone({ id: 1, target_value: null, is_completed: true }),
        ];

        const wrapper = mount(Timeline, {
            props: { record: makeGoal({ milestones }) },
            global: { stubs },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('cursor-pointer');
        expect(button.attributes('disabled')).toBeUndefined();
    });
});
