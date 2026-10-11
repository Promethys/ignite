<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import AppearanceTabs from '@/components/settings/AppearanceTabs.vue';
import LocaleSelect from '@/components/settings/LocaleSelect.vue';
import ThemeList from '@/components/settings/ThemeList.vue';
import { type BreadcrumbItem } from '@/types';
import type { Theme } from '@/types/models';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

defineProps<{
    themes: Theme[];
    themeLimit: number;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'settings.appearance.breadcrumb',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.appearance.head')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.appearance.title')"
                    :description="$t('settings.appearance.description')"
                />
                <AppearanceTabs />
            </div>

            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.themes.title')"
                    :description="$t('settings.themes.description')"
                />
                <ThemeList :themes="themes" :theme-limit="themeLimit" />
            </div>

            <div class="space-y-6">
                <HeadingSmall
                    :title="$t('settings.appearance.language')"
                    :description="
                        $t('settings.appearance.language_description')
                    "
                />
                <LocaleSelect />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
