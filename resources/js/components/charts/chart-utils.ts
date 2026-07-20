export const readCssVar = (name: string): string =>
    typeof document !== 'undefined'
        ? getComputedStyle(document.documentElement)
              .getPropertyValue(name)
              .trim()
        : '';

export const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
