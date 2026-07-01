import type { FormDataConvertible } from '@inertiajs/core';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    action?: {
        label: string;
        method: 'patch' | 'post' | 'put' | 'delete';
        url: string;
        data?: Record<string, FormDataConvertible>
    }
};
