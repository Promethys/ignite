<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import categories from '@/routes/categories';
import goals from '@/routes/goals';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Crosshair, Folder, LayoutDashboard } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();

const mainNavItems: NavItem[] = [
    {
        title: 'common.nav.dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'common.nav.goals',
        href: goals.index(),
        icon: Crosshair,
        isActive: page.url.includes('/goals'),
    },
    {
        title: 'common.nav.categories',
        href: categories.index(),
        icon: Folder,
    },
    // {
    //     title: 'Progress',
    //     href: progress(),
    //     icon: TrendingUp,
    // },
    // {
    //     title: 'Achievements',
    //     href: achievements.index(),
    //     icon: Trophy,
    // },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
