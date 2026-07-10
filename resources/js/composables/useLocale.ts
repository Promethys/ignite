import { usePage } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import moment from 'moment';
import { ref } from 'vue';

export function useLocale() {
    const page = usePage();
    const supported = page.props.supportedLocales as Record<string, string>;

    const current = ref<string>(page.props.locale as string);

    async function switchTo(code: string): Promise<void> {
        if (!(code in supported)) {
            return;
        }

        await loadLanguageAsync(code);
        moment.locale(code);
        current.value = code;
    }

    return { current, supported, switchTo };
}
