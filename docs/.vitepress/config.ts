import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Ignite',
  description: 'Developer documentation for Ignite, a Laravel + Vue goal tracking app',
  cleanUrls: true,
  srcExclude: ['superpowers/**', 'tmp/**', 'use-containerized-services/**'],
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/getting-started' },
      { text: 'Features', link: '/features/goal-types' },
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/' },
          { text: 'Getting Started', link: '/getting-started' },
          { text: 'Installation', link: '/installation' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/Promethys/ignite' },
    ],
  },
})
