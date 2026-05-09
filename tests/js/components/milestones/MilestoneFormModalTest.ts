import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import type { Goal, Milestone } from '@/types/models';

const { mockUseForm, mockStore, mockUpdate } = vi.hoisted(() => ({
    mockUseForm: vi.fn(),
    mockStore: vi.fn(({ goal }: { goal: Goal }) => ({
        method: 'post',
        url: `/goals/${goal.id}/milestones`,
    })),
    mockUpdate: vi.fn(({ goal, milestone }: { goal: Goal; milestone: Milestone }) => ({
        method: 'put',
        url: `/goals/${goal.id}/milestones/${milestone.id}`,
    })),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: mockUseForm,
}));

vi.mock('@/actions/App/Http/Controllers/MilestoneController', () => ({
    store: mockStore,
    update: mockUpdate,
}));

vi.mock('lucide-vue-next', () => ({
    Plus: { template: '<span class="icon-plus" />' },
    Edit: { template: '<span class="icon-edit" />' },
}));

const createMockForm = (
    data: Record<string, unknown>,
    overrides: Record<string, unknown> = {},
) => ({
    ...data,
    errors: {} as Record<string, string>,
    processing: false,
    submit: vi.fn(),
    reset: vi.fn(),
    clearErrors: vi.fn(),
    transform: vi.fn(),
    ...overrides,
});

const baseGoal: Goal = {
    id: 1,
    user_id: 1,
    category_id: null,
    title: 'Save money',
    description: 'Save 1000 euros',
    icon: null,
    type: 'quantifiable',
    direction: 'ascending',
    target_value: 1000,
    initial_value: 0,
    current_value: 500,
    unit: 'euros',
    recurrence: null,
    start_date: '2026-01-01',
    deadline: '2026-12-31',
    completed_at: null,
    status: 'in_progress',
    priority: 'medium',
    points: 100,
    is_public: false,
    order: 1,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    progress_percentage: 50,
    is_overdue: false,
    is_completed: false,
};

const baseMilestone: Milestone = {
    id: 10,
    goal_id: 1,
    title: 'Reach 25% completion',
    description: 'Reach 250 euros saved',
    target_value: 250,
    order: 1,
    is_completed: false,
    is_reached: false,
    completed_at: null,
    points_reward: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

const stubs = {
    Dialog: {
        name: 'Dialog',
        props: ['open'],
        template: '<div><slot /></div>',
    },
    DialogTrigger: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogHeader: { template: '<div><slot /></div>' },
    DialogTitle: { template: '<h2><slot /></h2>' },
    DialogDescription: { template: '<p><slot /></p>' },
    DialogFooter: { template: '<div><slot /></div>' },
    DialogClose: { template: '<div><slot /></div>' },
    Input: { template: '<input />', props: ['modelValue'] },
    Label: { template: '<label><slot /></label>' },
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    Button: {
        template: '<button :disabled="disabled"><slot /></button>',
        props: ['disabled', 'variant', 'size'],
    },
    InputError: {
        template: '<p class="input-error">{{ message }}</p>',
        props: ['message'],
    },
    Spinner: { template: '<span class="spinner" />' },
};

describe('MilestoneFormModal', () => {
    beforeEach(() => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data);
        });
        mockStore.mockClear();
        mockUpdate.mockClear();
    });

    // =========================================================================
    // CREATE MODE
    // =========================================================================

    it('renders in create mode when no record prop is given', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Create a milestone');
        expect(wrapper.text()).toContain(
            'Create a milestone and track key checkpoints',
        );
        expect(wrapper.text()).toContain('Create');
    });

    it('shows the default create trigger button with Plus icon and Milestone text', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.find('.icon-plus').exists()).toBe(true);
        expect(wrapper.text()).toContain('Milestone');
    });

    it('initializes form with empty fields in create mode', () => {
        mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(mockUseForm).toHaveBeenCalledWith(
            'MilestoneCreateForm',
            expect.objectContaining({
                title: '',
                description: '',
                target_value: undefined,
                points_reward: 0,
            }),
        );
    });

    it('calls store action with the goal in create mode', () => {
        mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(mockStore).toHaveBeenCalledWith({ goal: baseGoal });
        expect(mockUpdate).not.toHaveBeenCalled();
    });

    // =========================================================================
    // EDIT MODE
    // =========================================================================

    it('renders in edit mode when record prop is provided', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal, record: baseMilestone },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Edit a milestone');
        expect(wrapper.text()).toContain('Edit your milestone.');
        expect(wrapper.text()).toContain('Edit');
    });

    it('shows edit trigger button with Edit icon in edit mode', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal, record: baseMilestone },
            global: { stubs },
        });

        expect(wrapper.find('.icon-edit').exists()).toBe(true);
        expect(wrapper.find('.icon-plus').exists()).toBe(false);
    });

    it('pre-fills form fields with record values in edit mode', () => {
        mount(MilestoneFormModal, {
            props: { goal: baseGoal, record: baseMilestone },
            global: { stubs },
        });

        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Reach 25% completion',
                description: 'Reach 250 euros saved',
                target_value: 250,
                points_reward: 0,
            }),
        );
    });

    it('calls update action with goal and milestone in edit mode', () => {
        mount(MilestoneFormModal, {
            props: { goal: baseGoal, record: baseMilestone },
            global: { stubs },
        });

        expect(mockUpdate).toHaveBeenCalledWith({
            goal: baseGoal,
            milestone: baseMilestone,
        });
        expect(mockStore).not.toHaveBeenCalled();
    });

    it('uses unnamed form in edit mode', () => {
        mount(MilestoneFormModal, {
            props: { goal: baseGoal, record: baseMilestone },
            global: { stubs },
        });

        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Reach 25% completion',
            }),
        );
    });

    // =========================================================================
    // FORM STRUCTURE
    // =========================================================================

    it('renders title input with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(labels.some((l) => l.text() === 'Title')).toBe(true);
    });

    it('renders description textarea with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(labels.some((l) => l.text() === 'Description')).toBe(true);
    });

    it('renders target value input with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(labels.some((l) => l.text() === 'Target Value')).toBe(true);
    });

    // =========================================================================
    // FORM ERRORS
    // =========================================================================

    it('displays title error when present', () => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data, {
                errors: { title: 'Title is required' },
            });
        });

        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Title is required');
    });

    it('displays description error when present', () => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data, {
                errors: { description: 'Description is too long' },
            });
        });

        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Description is too long');
    });

    it('displays target_value error when present', () => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data, {
                errors: { target_value: 'Target value must be a number' },
            });
        });

        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Target value must be a number');
    });

    it('does not display any errors when form has no errors', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const errors = wrapper.findAll('.input-error');
        expect(errors).toHaveLength(0);
    });

    // =========================================================================
    // SUBMIT BUTTON
    // =========================================================================

    it('disables submit button when form is processing', () => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data, { processing: true });
        });

        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Create'));
        expect(submitBtn?.attributes('disabled')).toBeDefined();
    });

    it('does not disable submit button when form is not processing', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Create'));
        expect(submitBtn?.attributes('disabled')).toBeUndefined();
    });

    it('shows spinner when form is processing', () => {
        mockUseForm.mockImplementation((...args: unknown[]) => {
            const data = (args.length === 2 ? args[1] : args[0]) as Record<string, unknown>;
            return createMockForm(data, { processing: true });
        });

        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.find('.spinner').exists()).toBe(true);
    });

    it('does not show spinner when form is not processing', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.find('.spinner').exists()).toBe(false);
    });

    // =========================================================================
    // CANCEL BUTTON
    // =========================================================================

    it('renders a cancel button', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Cancel');
    });

    // =========================================================================
    // DIALOG OPEN STATE
    // =========================================================================

    it('opens dialog when open prop is true', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal, open: true },
            global: { stubs },
        });

        expect(wrapper.findComponent({ name: 'Dialog' }).props('open')).toBe(true);
    });

    it('does not open dialog when open prop is not provided', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal: baseGoal },
            global: { stubs },
        });

        expect(wrapper.findComponent({ name: 'Dialog' }).props('open')).toBe(false);
    });
});
