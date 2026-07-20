import type { ChartOptions } from '@/lib/chart-theme';
import { useMutationObserver } from '@vueuse/core';
import { computed, ref, type ComputedRef } from 'vue';

/**
 * Wraps a chart options factory so its CSS variable reads re-run whenever the
 * theme changes.
 *
 * The factory reads computed styles, which are not reactive. Watching the
 * `dark` class on the document element gives a signal that fires after the
 * variables have already flipped, and it catches every path that changes the
 * theme: the appearance setting, and the system-preference listener.
 */
export function useChartTheme(
    factory: () => ChartOptions,
): ComputedRef<ChartOptions> {
    const revision = ref(0);

    useMutationObserver(
        document.documentElement,
        () => {
            revision.value += 1;
        },
        { attributes: true, attributeFilter: ['class'] },
    );

    return computed(() => {
        // Referenced so the computed invalidates when the theme flips.
        void revision.value;

        return factory();
    });
}
