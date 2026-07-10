import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import NavMain from '@/components/NavMain.vue';
import { LayoutDashboard } from 'lucide-vue-next';

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    usePage: () => ({ url: '/dashboard' }),
}));

vi.mock('@/components/ui/sidebar', () => ({
    SidebarGroup: { template: '<div><slot /></div>' },
    SidebarMenu: { template: '<div><slot /></div>' },
    SidebarMenuItem: { template: '<div><slot /></div>' },
    SidebarMenuButton: {
        props: ['asChild', 'isActive', 'tooltip'],
        template: '<div><slot /></div>',
    },
}));

const fr: Record<string, string> = {
    'common.nav.dashboard': 'Tableau de bord',
    'common.nav.goals': 'Objectifs',
    'common.nav.categories': 'Catégories',
};

const items = [
    { title: 'common.nav.dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'common.nav.goals', href: '/goals', icon: LayoutDashboard },
    { title: 'common.nav.categories', href: '/categories', icon: LayoutDashboard },
];

describe('NavMain', () => {
    it('renders nav item titles in French', () => {
        const wrapper = mount(NavMain, {
            props: { items },
            global: {
                mocks: {
                    $t: (key: string) => fr[key] ?? key,
                },
            },
        });

        expect(wrapper.text()).toContain('Tableau de bord');
        expect(wrapper.text()).toContain('Objectifs');
        expect(wrapper.text()).toContain('Catégories');
    });
});
