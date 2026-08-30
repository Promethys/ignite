import { useQueryFilters } from '@/composables/useQueryFilters';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const setUrl = (url: string, state: Record<string, unknown> = {}) =>
    window.history.replaceState(state, '', url);

const flushUrlWrite = async () => {
    await nextTick();
    vi.advanceTimersByTime(300);
    await nextTick();
};

describe('useQueryFilters', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        setUrl('/goals');
    });

    afterEach(() => {
        vi.useRealTimers();
        setUrl('/');
    });

    it('seeds every value from the query string', () => {
        setUrl('/goals?status=paused&search=book');

        const { filters } = useQueryFilters({
            status: 'all',
            search: '',
            category: 'all',
        });

        expect(filters.status).toBe('paused');
        expect(filters.search).toBe('book');
        expect(filters.category).toBe('all');
    });

    it('ignores query keys it was not given a default for', () => {
        setUrl('/goals?status=paused&nonsense=1');

        const { filters } = useQueryFilters({ status: 'all' });

        expect(filters).not.toHaveProperty('nonsense');
        expect(Object.keys(filters)).toEqual(['status']);
    });

    it('falls back to the default when a param is present but empty', () => {
        setUrl('/goals?search=&status=');

        const { filters } = useQueryFilters({
            search: 'fallback',
            status: 'all',
        });

        expect(filters.search).toBe('fallback');
        expect(filters.status).toBe('all');
    });

    it('serializes only the values that differ from their default', async () => {
        const { filters } = useQueryFilters(
            { status: 'all', search: '' },
            { writeUrl: true },
        );

        filters.status = 'paused';
        await flushUrlWrite();

        expect(window.location.search).toBe('?status=paused');
    });

    it('drops a value from the url once it returns to its default', async () => {
        const { filters } = useQueryFilters(
            { status: 'all', search: '' },
            { writeUrl: true },
        );

        filters.status = 'paused';
        await flushUrlWrite();

        expect(window.location.search).toBe('?status=paused');

        filters.status = 'all';
        await flushUrlWrite();

        expect(window.location.search).toBe('');
        expect(window.location.pathname).toBe('/goals');
    });

    it('reset restores the defaults and clears the url', async () => {
        const { filters, reset } = useQueryFilters(
            { status: 'all', search: '' },
            { writeUrl: true },
        );

        filters.status = 'paused';
        filters.search = 'book';
        await flushUrlWrite();

        expect(window.location.search).toBe('?status=paused&search=book');

        reset();
        await flushUrlWrite();

        expect(filters.status).toBe('all');
        expect(filters.search).toBe('');
        expect(window.location.search).toBe('');
    });

    it('never touches the url when writeUrl is off', async () => {
        setUrl('/goals/1/entries?search=a');

        const { filters } = useQueryFilters({ search: '' });

        filters.search = 'b';
        await flushUrlWrite();

        expect(filters.search).toBe('b');
        expect(window.location.search).toBe('?search=a');
    });

    it('preserves the existing history state when writing the url', async () => {
        setUrl('/goals', { page: { component: 'Goals/Index' } });

        const { filters } = useQueryFilters(
            { status: 'all' },
            { writeUrl: true },
        );

        filters.status = 'completed';
        await flushUrlWrite();

        expect(window.location.search).toBe('?status=completed');
        expect(window.history.state.page).toEqual({
            component: 'Goals/Index',
        });
    });

    it('debounces the url write across rapid changes', async () => {
        const replaceState = vi.spyOn(window.history, 'replaceState');

        const { filters } = useQueryFilters({ search: '' }, { writeUrl: true });

        filters.search = 'b';
        await nextTick();
        filters.search = 'bo';
        await nextTick();
        filters.search = 'boo';
        await nextTick();
        await flushUrlWrite();

        expect(replaceState).toHaveBeenCalledTimes(1);
        expect(window.location.search).toBe('?search=boo');

        replaceState.mockRestore();
    });

    it('reports whether any value differs from its default', () => {
        const { filters, hasActiveFilters, reset } = useQueryFilters({
            status: 'all',
            search: '',
        });

        expect(hasActiveFilters.value).toBe(false);

        filters.search = 'book';

        expect(hasActiveFilters.value).toBe(true);

        reset();

        expect(hasActiveFilters.value).toBe(false);
    });

    it('reports active filters seeded straight from the url', () => {
        setUrl('/goals?status=paused');

        const { hasActiveFilters } = useQueryFilters({
            status: 'all',
            search: '',
        });

        expect(hasActiveFilters.value).toBe(true);
    });
});
