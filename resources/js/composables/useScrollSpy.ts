import {
    onMounted,
    onUnmounted,
    ref,
    toValue,
    watch,
    type MaybeRefOrGetter,
} from 'vue';

export function useScrollSpy(ids: MaybeRefOrGetter<string[]>, offset = 140) {
    const active = ref(toValue(ids)[0] ?? '');
    let frame: number | null = null;

    const update = () => {
        const sections = toValue(ids);
        let current = sections[0] ?? '';
        for (const id of sections) {
            const element = document.getElementById(id);
            if (element && element.getBoundingClientRect().top <= offset)
                current = id;
        }
        if (
            document.documentElement.scrollHeight > window.innerHeight &&
            window.innerHeight + window.scrollY >=
                document.documentElement.scrollHeight - 2
        )
            current = sections.at(-1) ?? current;
        active.value = current;
    };
    const schedule = () => {
        if (frame !== null) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            update();
        });
    };
    onMounted(() => {
        update();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
    });
    watch(() => toValue(ids), schedule);
    onUnmounted(() => {
        window.removeEventListener('scroll', schedule);
        window.removeEventListener('resize', schedule);
        if (frame !== null) cancelAnimationFrame(frame);
    });
    return active;
}
