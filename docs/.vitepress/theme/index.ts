import DefaultTheme from 'vitepress/theme';
import { h } from 'vue';
import MarkdownLink from './MarkdownLink.vue';
import './style.css';

export default {
    extends: DefaultTheme,
    Layout: () =>
        h(DefaultTheme.Layout, null, {
            'doc-footer-before': () => h(MarkdownLink),
        }),
};
