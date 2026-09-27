import {
    insertNodeAt,
    removeNode,
    useSortable,
} from '@vueuse/integrations/useSortable';
import type { SortableEvent } from 'sortablejs';
import { nextTick, watch, type Ref } from 'vue';

export const DRAG_HANDLE_ATTRIBUTE = 'data-drag-handle';

export function useReorderableList<Item>(
    listElement: Ref<HTMLElement | null>,
    items: Ref<Item[]>,
    options: {
        keyOf: (item: Item) => string | number;
        enabled?: boolean;
        onReorder?: (items: Item[]) => void;
    },
) {
    const applyMove = (from: number, to: number) => {
        const reordered = [...items.value];
        reordered.splice(to, 0, ...reordered.splice(from, 1));
        items.value = reordered;
        options.onReorder?.(reordered);
    };

    const sortable = useSortable(listElement, items, {
        handle: `[${DRAG_HANDLE_ATTRIBUTE}]`,
        animation: 150,
        disabled: options.enabled === false,
        onUpdate: (event: SortableEvent) => {
            const from = event.oldIndex ?? 0;
            const to = event.newIndex ?? 0;
            if (from === to) return;

            removeNode(event.item);
            insertNodeAt(event.from, event.item, from);
            applyMove(from, to);
        },
    });

    watch(listElement, (element) => {
        sortable.stop();
        if (element) sortable.start();
    });

    const move = async (from: number, to: number) => {
        if (to < 0 || to >= items.value.length) return;

        const movedKey = options.keyOf(items.value[from]);
        applyMove(from, to);

        await nextTick();
        listElement.value
            ?.querySelector<HTMLElement>(
                `[${DRAG_HANDLE_ATTRIBUTE}="${movedKey}"]`,
            )
            ?.focus();
    };

    return { move };
}
