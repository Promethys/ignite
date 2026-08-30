import { useDebounceFn } from '@vueuse/core';
import { computed, reactive, watch, type ComputedRef } from 'vue';

type FilterValues = Record<string, string>;

type QueryFilters<Filters extends FilterValues> = {
    filters: Filters;
    hasActiveFilters: ComputedRef<boolean>;
    reset: () => void;
};

const URL_WRITE_DEBOUNCE_MS = 300;

export function useQueryFilters<Filters extends FilterValues>(
    defaults: Filters,
    options: { writeUrl?: boolean } = {},
): QueryFilters<Filters> {
    const filterKeys = Object.keys(defaults) as (keyof Filters & string)[];
    const initialParams = new URLSearchParams(window.location.search);

    const filters = reactive(
        Object.fromEntries(
            filterKeys.map((key) => [
                key,
                initialParams.get(key) || defaults[key],
            ]),
        ),
    ) as Filters;

    const hasActiveFilters = computed(() =>
        filterKeys.some((key) => filters[key] !== defaults[key]),
    );

    const reset = () => {
        filterKeys.forEach((key) => {
            filters[key] = defaults[key];
        });
    };

    if (options.writeUrl) {
        const ownPathname = window.location.pathname;

        const writeUrl = useDebounceFn(() => {
            if (window.location.pathname !== ownPathname) {
                return;
            }

            const params = new URLSearchParams();

            filterKeys.forEach((key) => {
                if (filters[key] !== defaults[key]) {
                    params.set(key, filters[key]);
                }
            });

            const query = params.toString();
            const url = query
                ? `${window.location.pathname}?${query}`
                : window.location.pathname;

            window.history.replaceState({ ...window.history.state }, '', url);
        }, URL_WRITE_DEBOUNCE_MS);

        watch(filters, writeUrl, { deep: true });
    }

    return { filters, hasActiveFilters, reset };
}
