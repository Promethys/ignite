<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { AppPageProps, User } from '@/types';
import formbricks from '@formbricks/js';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LifeBuoy, LogOut, MessageSquare, Settings } from 'lucide-vue-next';

interface Props {
    user: User;
}

const handleLogout = () => {
    if (typeof import.meta.env.VITE_FORMBRICKS_WORKSPACE_ID !== 'undefined') {
        void formbricks.logout();
    }
    router.flushAll();
};

const supportEmail = usePage<AppPageProps>().props.supportEmail;

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="edit()"
                prefetch
                as="button"
            >
                <Settings class="mr-2 h-4 w-4" />
                {{ $t('common.actions.settings') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <a
                class="block w-full cursor-pointer"
                :href="`mailto:${supportEmail}?subject=${encodeURIComponent($t('common.support.email_subject'))}`"
                target="_blank"
                rel="noopener"
            >
                <LifeBuoy class="mr-2 h-4 w-4" />
                {{ $t('common.support.report_issue') }}
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <button
                id="send-feedback"
                type="button"
                class="block w-full cursor-pointer text-left"
            >
                <MessageSquare class="mr-2 inline h-4 w-4" />
                {{ $t('common.support.send_feedback') }}
            </button>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ $t('common.actions.log_out') }}
        </Link>
    </DropdownMenuItem>
</template>
