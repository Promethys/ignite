import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import type { Milestone } from '@/types/models';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { mockUseForm, mockStore, mockUpdate } = vi.hoisted(() => ({
    mockUseForm: vi.fn(),
    mockStore: vi.fn(({ goal }: { goal: number }) => ({
        method: 'post',
        url: `/goals/${goal}/milestones`,
    })),
    mockUpdate: vi.fn(
        ({ goal, milestone }: { goal: number; milestone: Milestone }) => ({
            method: 'put',
            url: `/goals/${goal}/milestones/${milestone.id}`,
        }),
    ),
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
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data),
        );
        mockStore.mockClear();
        mockUpdate.mockClear();
    });

    // =========================================================================
    // CREATE MODE
    // =========================================================================

    it('renders in create mode when no record prop is given', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.form.create_title');
        expect(wrapper.text()).toContain('milestones.form.create_description');
        expect(wrapper.text()).toContain('milestones.form.submit_create');
    });

    it('renders the French title when $t maps the key', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: {
                stubs,
                mocks: {
                    $t: (key: string) =>
                        key === 'milestones.form.create_title'
                            ? 'Créer un jalon'
                            : key,
                },
            },
        });

        expect(wrapper.text()).toContain('Créer un jalon');
    });

    it('shows the default create trigger button with Plus icon and Milestone text', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.find('.icon-plus').exists()).toBe(true);
        expect(wrapper.text()).toContain('milestones.trigger');
    });

    it('initializes form with empty fields in create mode', () => {
        mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                title: '',
                description: '',
                target_value: undefined,
            }),
        );
    });

    it('calls store action with the goal id in create mode', () => {
        mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(mockStore).toHaveBeenCalledWith({ goal: 1 });
        expect(mockUpdate).not.toHaveBeenCalled();
    });

    // =========================================================================
    // EDIT MODE
    // =========================================================================

    it('renders in edit mode when record prop is provided', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1, record: baseMilestone },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('milestones.form.edit_title');
        expect(wrapper.text()).toContain('milestones.form.edit_description');
        expect(wrapper.text()).toContain('milestones.form.submit_edit');
    });

    it('shows edit trigger button with Edit icon in edit mode', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1, record: baseMilestone },
            global: { stubs },
        });

        expect(wrapper.find('.icon-edit').exists()).toBe(true);
        expect(wrapper.find('.icon-plus').exists()).toBe(false);
    });

    it('pre-fills form fields with record values in edit mode', () => {
        mount(MilestoneFormModal, {
            props: { goal_id: 1, record: baseMilestone },
            global: { stubs },
        });

        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Reach 25% completion',
                description: 'Reach 250 euros saved',
                target_value: 250,
            }),
        );
    });

    it('calls update action with the milestone goal id and milestone in edit mode', () => {
        mount(MilestoneFormModal, {
            props: { goal_id: 1, record: baseMilestone },
            global: { stubs },
        });

        expect(mockUpdate).toHaveBeenCalledWith({
            goal: baseMilestone.goal_id,
            milestone: baseMilestone,
        });
        expect(mockStore).not.toHaveBeenCalled();
    });

    // =========================================================================
    // FORM STRUCTURE
    // =========================================================================

    it('renders title input with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(labels.some((l) => l.text() === 'milestones.form.title')).toBe(
            true,
        );
    });

    it('renders description textarea with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(
            labels.some((l) => l.text() === 'milestones.form.description'),
        ).toBe(true);
    });

    it('renders target value input with label', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const labels = wrapper.findAll('label');
        expect(
            labels.some((l) => l.text() === 'milestones.form.target_value'),
        ).toBe(true);
    });

    // =========================================================================
    // FORM ERRORS
    // =========================================================================

    it('displays title error when present', () => {
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data, { errors: { title: 'Title is required' } }),
        );

        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Title is required');
    });

    it('displays description error when present', () => {
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data, {
                errors: { description: 'Description is too long' },
            }),
        );

        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Description is too long');
    });

    it('displays target_value error when present', () => {
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data, {
                errors: { target_value: 'Target value must be a number' },
            }),
        );

        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Target value must be a number');
    });

    it('does not display any errors when form has no errors', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const errors = wrapper.findAll('.input-error');
        expect(errors).toHaveLength(0);
    });

    // =========================================================================
    // SUBMIT BUTTON
    // =========================================================================

    it('disables submit button when form is processing', () => {
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data, { processing: true }),
        );

        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) =>
            b.text().includes('milestones.form.submit_create'),
        );
        expect(submitBtn?.attributes('disabled')).toBeDefined();
    });

    it('does not disable submit button when form is not processing', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) =>
            b.text().includes('milestones.form.submit_create'),
        );
        expect(submitBtn?.attributes('disabled')).toBeUndefined();
    });

    it('shows spinner when form is processing', () => {
        mockUseForm.mockImplementation((data: Record<string, unknown>) =>
            createMockForm(data, { processing: true }),
        );

        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.find('.spinner').exists()).toBe(true);
    });

    it('does not show spinner when form is not processing', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.find('.spinner').exists()).toBe(false);
    });

    // =========================================================================
    // CANCEL BUTTON
    // =========================================================================

    it('renders a cancel button', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('common.actions.cancel');
    });

    // =========================================================================
    // DIALOG OPEN STATE
    // =========================================================================

    it('opens dialog when open prop is true', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1, open: true },
            global: { stubs },
        });

        expect(wrapper.findComponent({ name: 'Dialog' }).props('open')).toBe(
            true,
        );
    });

    it('does not open dialog when open prop is not provided', () => {
        const wrapper = mount(MilestoneFormModal, {
            props: { goal_id: 1 },
            global: { stubs },
        });

        expect(wrapper.findComponent({ name: 'Dialog' }).props('open')).toBe(
            false,
        );
    });
});
