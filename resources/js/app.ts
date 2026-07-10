import '../css/app.css';

import formbricks from '@formbricks/js';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import moment from 'moment';
import 'moment/locale/fr';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { initializeTheme } from './composables/useAppearance';
import { initializeFlashToast } from './lib/flashToast';

if (typeof window !== 'undefined') {
    formbricks.setup({
        environmentId: 'cmh7uef6q0rtzad01j1mpxxwl',
        appUrl: 'https://app.formbricks.com',
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const sharedLocale = props.initialPage.props.locale as string;

        moment.locale(sharedLocale);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                lang: sharedLocale,
                resolve: (lang) => {
                    const langs = import.meta.glob<{
                        default: Record<string, string>;
                    }>('../../lang/*.json', { eager: true });
                    return {
                        ...(langs[`../../lang/php_${lang}.json`]?.default ??
                            {}),
                        ...(langs[`../../lang/${lang}.json`]?.default ?? {}),
                    };
                },
            })
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
