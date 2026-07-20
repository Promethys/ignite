import '../css/app.css';

import formbricks from '@formbricks/js';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import moment from 'moment';
import 'moment/locale/fr';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { initializeTheme } from './composables/useAppearance';
import { initializeFlashToast } from './lib/flashToast';
import { formbricksEnabled } from './lib/formbricks';
import { bootI18n, bootLocale, resolveLang } from './lib/i18n';

const ENV = import.meta.env;
const formbricksWorkspaceID = ENV.VITE_FORMBRICKS_WORKSPACE_ID;
const formbricksAppURL = ENV.VITE_FORMBRICKS_APP_URL;

const formbricksReady =
    typeof window !== 'undefined' && formbricksEnabled()
        ? formbricks.setup({
              workspaceId: formbricksWorkspaceID,
              appUrl: formbricksAppURL ?? 'https://app.formbricks.com',
          })
        : Promise.resolve();

const appName = ENV.VITE_APP_NAME || 'Laravel';

type formbricksUser = { id: number; name: string; email: string };
let identifiedUserId: string | null = null;

function identifyFormbricks(user: formbricksUser | undefined) {
    void formbricksReady.then(async () => {
        if (user && String(user.id) !== identifiedUserId) {
            await formbricks.setUserId(String(user.id));
            await formbricks.setAttributes({
                name: user.name,
                email: user.email,
            });
            identifiedUserId = String(user.id);
        } else if (!user) {
            identifiedUserId = null;
        }
    });
}

const locale = bootLocale();

// Messages must be in place before the app mounts, otherwise `trans()` calls in
// `<script setup>` capture the raw key. See `lib/i18n.ts`.
await bootI18n(locale);

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const initialProps = props.initialPage.props;
        const sharedLocale = initialProps.locale as string;

        moment.locale(sharedLocale);

        identifyFormbricks(
            initialProps.auth?.user as formbricksUser | undefined,
        );
        void formbricksReady.then(() => formbricks.setLanguage(sharedLocale));

        router.on('navigate', (event) => {
            const props = event.detail.page.props;
            identifyFormbricks(props.auth?.user as formbricksUser | undefined);
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            // Reuses the instance warmed by bootI18n, so install applies the
            // messages synchronously rather than a microtask after mount.
            .use(i18nVue, {
                lang: sharedLocale,
                resolve: resolveLang,
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
