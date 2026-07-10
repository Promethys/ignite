import CategoriesIndex from '@/pages/Categories/Index.vue';
import type { Category } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// <Head> needs the app head manager, absent in unit tests; stub only that export.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        router: { delete: vi.fn() },
    };
});

const passthrough = (names: string[]) =>
    Object.fromEntries(
        names.map((n) => [n, { template: '<div><slot /></div>' }]),
    );

const stubs = {
    ...passthrough([
        'AppLayout',
        'PageHeader',
        'CategoryFormModal',
        'Empty',
        'EmptyHeader',
        'EmptyMedia',
        'EmptyTitle',
        'EmptyDescription',
        'EmptyContent',
        'AlertDialog',
        'AlertDialogTrigger',
        'AlertDialogContent',
        'AlertDialogHeader',
        'AlertDialogTitle',
        'AlertDialogDescription',
        'AlertDialogFooter',
        'AlertDialogCancel',
        'AlertDialogAction',
        'Button',
    ]),
    Link: { template: '<a><slot /></a>' },
};

const makeCategory = (overrides: Partial<Category>): Category =>
    ({
        id: 1,
        name: 'Category',
        slug: 'category',
        description: null,
        color: '#7c3aed',
        icon: null,
        user_id: 1,
        order: 0,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
        goals_count: 0,
        active_goals_count: 0,
        completed_goals_count: 0,
        ...overrides,
    }) as Category;

const mountIndex = (items: Category[], openCreate = false) =>
    mount(CategoriesIndex, {
        props: { items, openCreate },
        global: { stubs },
    });

describe('Categories/Index', () => {
    it('renders a card for each category', () => {
        const wrapper = mountIndex([
            makeCategory({ id: 1, name: 'Health' }),
            makeCategory({ id: 2, name: 'Career' }),
        ]);

        expect(wrapper.text()).toContain('Health');
        expect(wrapper.text()).toContain('Career');
    });

    it('sizes the completion bar from completed / total goals', () => {
        const wrapper = mountIndex([
            makeCategory({
                name: 'Health',
                goals_count: 4,
                active_goals_count: 2,
                completed_goals_count: 2,
            }),
        ]);

        // 2 of 4 completed → 50%
        expect(wrapper.html()).toContain('width: 50%');
    });

    it('shows 0% completion for a category with no goals', () => {
        const wrapper = mountIndex([makeCategory({ goals_count: 0 })]);

        expect(wrapper.html()).toContain('width: 0%');
    });

    it('renders the empty state when there are no categories', () => {
        const wrapper = mountIndex([]);

        expect(wrapper.text()).toContain('categories.empty.title');
    });
});
