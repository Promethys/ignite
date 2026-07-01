import type { FlashToast } from '@/types/ui';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        const action = data.action;

        if (action) {
            toast[data.type](data.message, {
                action: {
                    label: action.label,
                    onClick: () =>
                        router[action.method](action.url, action.data),
                },
            });
        } else {
            toast[data.type](data.message);
        }
    });
}
