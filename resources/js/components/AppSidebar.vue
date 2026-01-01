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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    Crosshair,
    LayoutDashboard,
    Folder, 
    TrendingUp, 
    Trophy 
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import goals from '@/routes/goals';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Goals',
        href: goals.index(),
        icon: Crosshair,
        isActive: function () {
            return document.location.href.includes('goals');
        }()
    },
    // {
    //     title: 'Categories',
    //     href: categories.index(),
    //     icon: Folder,
    // },
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
