import { config } from '@vue/test-utils';

config.global.stubs = {
    Link: true,
    InertiaLink: true,
};

// The laravel-vue-i18n plugin is not installed in unit tests, so the `$t` and
// `$tChoice` template globals are unavailable. Provide key-passthrough mocks so
// every component renders; tests assert on the translation key. Replacement
// values are appended so interpolated numbers (streak counts, totals) stay
// visible to assertions. A test that needs to prove a French string renders
// overrides `$t` per mount with a small key -> value map.
const withReplacements = (
    key: string,
    replacements?: Record<string, unknown>,
) => {
    const values = replacements ? Object.values(replacements) : [];
    return values.length ? `${key} ${values.join(' ')}` : key;
};

config.global.mocks = {
    $t: (key: string, replacements?: Record<string, unknown>) =>
        withReplacements(key, replacements),
    $tChoice: (
        key: string,
        _number?: number,
        replacements?: Record<string, unknown>,
    ) => withReplacements(key, replacements),
};
