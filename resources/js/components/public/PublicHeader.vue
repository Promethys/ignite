<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import AppLogo from '../AppLogo.vue';
import LanguageSwitcher from '../LanguageSwitcher.vue';
import Button from '../ui/button/Button.vue';
</script>

<template>
    <header class="border-b">
        <div
            class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6"
        >
            <div class="flex items-center gap-2 text-sm">
                <AppLogo />
            </div>
            <nav class="flex items-center gap-2">
                <LanguageSwitcher />
                <template v-if="$page.props.auth.user">
                    <Button as-child variant="inverse" class="font-semibold">
                        <Link :href="dashboard()">
                            {{ $t('common.nav.dashboard') }}
                        </Link>
                    </Button>
                </template>
                <template v-else>
                    <Button variant="outline" as-child>
                        <Link :href="login()">
                            {{ $t('common.actions.log_in') }}
                        </Link>
                    </Button>
                    <Button as-child variant="inverse" class="font-semibold">
                        <Link :href="register()">
                            {{ $t('common.actions.get_started') }}
                        </Link>
                    </Button>
                </template>
            </nav>
        </div>
    </header>
</template>
