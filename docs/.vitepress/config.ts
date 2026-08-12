import { defineConfig } from 'vitepress';
import { withMermaid } from 'vitepress-plugin-mermaid';

// Evaluated at build time, so the footer year follows each deploy instead of
// needing an edit every January. The LICENSE notice is deliberately not
// derived from this: it marks the year of publication, not the current one.
const firstPublished = 2026;
const currentYear = new Date().getFullYear();
const copyrightYears =
    currentYear > firstPublished
        ? `${firstPublished}-${currentYear}`
        : `${firstPublished}`;

// https://vitepress.dev/reference/site-config
export default withMermaid(
    defineConfig({
        title: 'Ignite',
        description:
            'Documentation for Ignite, a goal tracking app you can use or self-host',
        cleanUrls: true,
        srcExclude: [
            'superpowers/**',
            'tmp/**',
            'use-containerized-services/**',
        ],
        head: [
            [
                'link',
                { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
            ],
            ['link', { rel: 'preconnect', href: 'https://fonts.bunny.net' }],
            [
                'link',
                {
                    rel: 'stylesheet',
                    href: 'https://fonts.bunny.net/css?family=fraunces:600,700|inter:400,500,600',
                },
            ],
        ],
        markdown: {
            image: { lazyLoading: true },
            config: (md) => {
                md.renderer.rules.table_open = () =>
                    '<div class="table-wrapper" tabindex="0">\n<table>\n';
                md.renderer.rules.table_close = () => '</table>\n</div>\n';
            },
        },
        themeConfig: {
            logo: {
                light: '/logo-light.svg',
                dark: '/logo-dark.svg',
                alt: 'Ignite',
            },
            nav: [
                { text: 'Using Ignite', link: '/guide/' },
                { text: 'Developers', link: '/getting-started' },
            ],
            sidebar: {
                '/guide/': [
                    {
                        text: 'Using Ignite',
                        items: [
                            { text: 'Getting Started', link: '/guide/' },
                            { text: 'Goal Types', link: '/guide/goal-types' },
                            {
                                text: 'Tracking Progress',
                                link: '/guide/tracking-progress',
                            },
                        ],
                    },
                ],
                '/': [
                    {
                        text: 'Introduction',
                        items: [
                            {
                                text: 'Getting Started',
                                link: '/getting-started',
                            },
                            { text: 'Installation', link: '/installation' },
                        ],
                    },
                    {
                        text: 'Configuration',
                        items: [
                            { text: 'Configuration', link: '/configuration' },
                        ],
                    },
                    {
                        text: 'Features',
                        items: [
                            {
                                text: 'Authentication',
                                link: '/features/authentication',
                            },
                            {
                                text: 'Goal Types',
                                link: '/features/goal-types',
                            },
                            {
                                text: 'Categories',
                                link: '/features/categories',
                            },
                            { text: 'Streaks', link: '/features/streaks' },
                            {
                                text: 'Milestones',
                                link: '/features/milestones',
                            },
                            {
                                text: 'MCP Server',
                                link: '/features/mcp-server',
                            },
                            {
                                text: 'Internationalization',
                                link: '/features/internationalization',
                            },
                            {
                                text: 'Admin Panel',
                                link: '/features/admin-panel',
                            },
                            {
                                text: 'Feedback & Ops',
                                link: '/features/feedback-and-ops',
                            },
                        ],
                    },
                    {
                        text: 'Deployment',
                        items: [
                            { text: 'Self-Hosting', link: '/self-hosting' },
                        ],
                    },
                    {
                        text: 'Contributing',
                        items: [
                            { text: 'Architecture', link: '/architecture' },
                            { text: 'Testing', link: '/testing' },
                        ],
                    },
                ],
            },
            socialLinks: [
                { icon: 'github', link: 'https://github.com/Promethys/ignite' },
            ],
            footer: {
                message:
                    'Source-available under the FSL-1.1-MIT license. <a href="https://github.com/sponsors/nirine1">Sponsoring</a> is optional and supports development.',
                copyright: `Copyright © ${copyrightYears} Ilainiriko Tambaza`,
            },
        },
    }),
);
