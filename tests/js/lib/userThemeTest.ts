import { applyThemeStylesheet } from '@/lib/userTheme';
import { beforeEach, describe, expect, it } from 'vitest';

const css = 'html:root{--primary:#2563eb;--primary-foreground:#ffffff}';

describe('applyThemeStylesheet', () => {
    beforeEach(() => {
        document.head.innerHTML = '';
    });

    it('fills the style element rendered by the server', () => {
        document.head.innerHTML = '<style id="user-theme"></style>';

        applyThemeStylesheet(css);

        expect(document.querySelectorAll('#user-theme')).toHaveLength(1);
        expect(document.getElementById('user-theme')?.textContent).toBe(css);
    });

    it('creates the style element when the page has none', () => {
        applyThemeStylesheet(css);

        expect(document.getElementById('user-theme')?.textContent).toBe(css);
    });

    it('clears the theme when the page carries none', () => {
        document.head.innerHTML = `<style id="user-theme">${css}</style>`;

        applyThemeStylesheet(null);

        expect(document.getElementById('user-theme')?.textContent).toBe('');
    });
});
