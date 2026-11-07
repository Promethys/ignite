import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import moment from 'moment';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function getDateDiffFromNow(date: string) {
    const now = moment();
    const otherDate = moment(date);

    return otherDate.diff(now, 'days');
}

export function formatDate(date: string) {
    return new Date(date).toLocaleDateString(moment.locale(), {
        month: 'short',
        day: 'numeric',
    });
}

export function formatDisplayDate(date: string) {
    return moment(date).format('ll');
}

export function toDateInputFormat(date: string) {
    return new Date(date).toLocaleDateString('en-CA');
}

export function nullToEmpty(value: string | null | undefined): string {
    return value ?? '';
}

export function nullToUndefined(
    value: number | null | undefined,
): number | undefined {
    return value ?? undefined;
}

export function getCopyrightYears() {
    const firstPublished = 2026;
    const currentYear = new Date().getFullYear();
    const copyrightYears =
        currentYear > firstPublished
            ? `${firstPublished}-${currentYear}`
            : `${firstPublished}`;

    return copyrightYears;
}
