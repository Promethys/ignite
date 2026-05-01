import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue';
import type { Category } from '@/types/models';

vi.mock('@inertiajs/vue3', () => ({
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        errors: {} as Record<string, string>,
        processing: false,
        submit: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
        transform: vi.fn(function (this: Record<string, unknown>, fn: (data: Record<string, unknown>) => Record<string, unknown>) {
            return { ...this, ...fn(data) };
        }),
    }),
}));

vi.mock('@/actions/App/Http/Controllers/CategoryController', () => ({
    store: () => ({ method: 'POST', url: '/categories' }),
    update: (_record: Category) => ({ method: 'PUT', url: `/categories/${_record?.id}` }),
}));

const stubs = {
    Dialog: {
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
    Button: { template: '<button><slot /></button>', props: ['disabled', 'variant'] },
    FormError: { template: '<span class="form-error">{{ error }}</span>', props: ['error'] },
    Spinner: { template: '<span class="spinner" />' },
};

describe('CategoryFormModal', () => {
    it('renders in create mode when no record prop is given', () => {
        const wrapper = mount(CategoryFormModal, {
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Create a category');
        expect(wrapper.text()).toContain('Create');
    });

    it('renders in edit mode with pre-filled values when record is provided', () => {
        const record: Category = {
            id: 1,
            name: 'Fitness',
            slug: 'fitness',
            description: 'All fitness goals',
            color: '#ff0000',
            icon: '💪',
            user_id: 1,
            order: 1,
            created_at: '2026-01-01',
            updated_at: '2026-01-01',
        };

        const wrapper = mount(CategoryFormModal, {
            props: { record },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Edit a category');
        expect(wrapper.text()).toContain('Edit');
    });
});
