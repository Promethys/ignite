import { router } from '@inertiajs/vue3';

const STYLE_ELEMENT_ID = 'user-theme';

export function applyThemeStylesheet(stylesheet: unknown): void {
    if (typeof document === 'undefined') {
        return;
    }

    let styleElement = document.getElementById(STYLE_ELEMENT_ID);

    if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = STYLE_ELEMENT_ID;
        document.head.appendChild(styleElement);
    }

    const css = typeof stylesheet === 'string' ? stylesheet : '';

    if (styleElement.textContent !== css) {
        styleElement.textContent = css;
    }
}

export function initializeUserTheme(): void {
    router.on('navigate', (event) => {
        applyThemeStylesheet(event.detail.page.props.themeStylesheet);
    });

    router.on('success', (event) => {
        applyThemeStylesheet(event.detail.page.props.themeStylesheet);
    });
}
