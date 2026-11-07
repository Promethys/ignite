<script setup lang="ts">
import { trans } from 'laravel-vue-i18n';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import ArticleFooterLinks from './ArticleFooterLinks.vue';
import PrevNextLinks from './PrevNextLinks.vue';
import type { GuideArticle, GuidePage } from './types';
const props = defineProps<{
    article: GuideArticle;
    previous: GuidePage | null;
    next: GuidePage | null;
}>();
const prose = ref<HTMLElement | null>(null);
let cleanup: (() => void)[] = [];
const enhance = async () => {
    cleanup.forEach((remove) => remove());
    cleanup = [];
    await nextTick();
    prose.value
        ?.querySelectorAll<HTMLAnchorElement>('.heading-permalink')
        .forEach((anchor) => {
            anchor.setAttribute(
                'aria-label',
                trans('public.link_to', {
                    title:
                        anchor.parentElement?.textContent
                            ?.replace(/#$/, '')
                            .trim() ?? '',
                }),
            );
        });
    prose.value?.querySelectorAll('pre').forEach((block) => {
        const code = block.querySelector('code');
        if (!code) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'docs-copy-button';
        button.textContent = trans('public.docs.copy');
        button.setAttribute('aria-label', trans('public.docs.copy'));
        button.setAttribute('aria-live', 'polite');
        let timeout: ReturnType<typeof setTimeout> | undefined;
        const copy = async () => {
            try {
                await navigator.clipboard.writeText(code.textContent ?? '');
                button.textContent = trans('public.docs.copied');
            } catch {
                button.textContent = trans('public.docs.copy_failed');
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                button.textContent = trans('public.docs.copy');
            }, 2500);
        };
        button.addEventListener('click', copy);
        block.appendChild(button);
        cleanup.push(() => {
            clearTimeout(timeout);
            button.removeEventListener('click', copy);
            button.remove();
        });
    });
    prose.value?.querySelectorAll('table').forEach((table) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'docs-table-scroll';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', trans('public.docs.table'));
        table.replaceWith(wrapper);
        wrapper.appendChild(table);
        cleanup.push(() => wrapper.replaceWith(table));
    });
};
onMounted(enhance);
watch(() => props.article, enhance);
onUnmounted(() => cleanup.forEach((remove) => remove()));
</script>

<template>
    <article>
        <div ref="prose" class="docs-prose" v-html="article.html" />
        <div class="mt-12 space-y-8">
            <PrevNextLinks
                :previous="previous"
                :next="next"
            /><ArticleFooterLinks
                :raw-url="article.rawUrl"
                :edit-url="article.editUrl"
            />
        </div>
    </article>
</template>
