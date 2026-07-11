import GoalForm from '@/components/goals/GoalForm.vue';
import type { User } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    useForm: (...args: unknown[]) => {
        const data = args[args.length - 1] as Record<string, unknown>;
        return {
            ...data,
            errors: {} as Record<string, string>,
            processing: false,
            submit: vi.fn(),
            reset: vi.fn(),
            clearErrors: vi.fn(),
            transform: vi.fn(function (this: Record<string, unknown>) {
                return this;
            }),
        };
    },
}));

vi.mock('@/actions/App/Http/Controllers/Goals/GoalController', () => ({
    store: () => ({ method: 'POST', url: '/goals' }),
    update: () => ({ method: 'PUT', url: '/goals/1' }),
}));

vi.mock('@/routes/categories', () => ({
    default: { index: () => ({ url: '/categories' }) },
}));

vi.mock('@/routes/goals', () => ({
    default: { index: () => ({ url: '/goals' }) },
}));

const stubs = {
    Card: { template: '<div><slot /></div>' },
    CardContent: { template: '<div><slot /></div>' },
    CardFooter: { template: '<div><slot /></div>' },
    Button: { template: '<button><slot /></button>' },
    Input: { template: '<input />', props: ['modelValue'] },
    Label: { template: '<label><slot /></label>' },
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    InputError: { template: '<span />', props: ['message'] },
    TextLink: { template: '<a><slot /></a>' },
    HelpTooltip: { template: '<span><slot /></span>' },
    Select: {
        props: ['modelValue'],
        template:
            '<div class="select" :data-value="modelValue"><slot /></div>',
    },
    SelectTrigger: { template: '<div><slot /></div>' },
    SelectContent: { template: '<div><slot /></div>' },
    SelectItem: { template: '<div />' },
    SelectValue: { template: '<div />' },
};

const mountForm = (props: Record<string, unknown> = {}) =>
    mount(GoalForm, { props, global: { stubs } });

describe('GoalForm', () => {
    it('seeds the category from the selectedCategory prop in create mode', () => {
        const user = { id: 1, categories: { '1': 'Health', '2': 'Learning' } } as unknown as User;

        const wrapper = mountForm({ user, selectedCategory: '1' });

        const categorySelect = wrapper.find('.select#category_id');
        expect(categorySelect.exists()).toBe(true);
        expect(categorySelect.attributes('data-value')).toBe('1');
    });

    it('leaves the category unset when no selectedCategory is provided', () => {
        const user = { id: 1, categories: { '1': 'Health' } } as unknown as User;

        const wrapper = mountForm({ user });

        expect(wrapper.find('.select#category_id').attributes('data-value')).toBeUndefined();
    });
});
