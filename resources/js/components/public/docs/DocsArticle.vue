<script setup lang="ts">
import { trans } from 'laravel-vue-i18n';
import { h, nextTick, onMounted, onUnmounted, ref, render, watch } from 'vue';
import ArticleFooterLinks from './ArticleFooterLinks.vue';
import CopyButton from './CopyButton.vue';
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
        const controls = document.createElement('div');
        const wrapper = document.createElement('div');
        wrapper.className = 'public-code-block';
        block.replaceWith(wrapper);
        wrapper.appendChild(block);
        wrapper.appendChild(controls);
        render(h(CopyButton, { code: code.textContent ?? '' }), controls);
        cleanup.push(() => {
            render(null, controls);
            wrapper.replaceWith(block);
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
