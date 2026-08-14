import type { SsoProvider } from '@/types/models';

const labels: Record<SsoProvider, string> = {
    google: 'Google',
    github: 'GitHub',
};

export function providerLabel(provider: string): string {
    return labels[provider as SsoProvider] ?? provider;
}
