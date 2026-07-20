import { defineConfig } from 'vitepress'
import { withMermaid } from 'vitepress-plugin-mermaid'

// https://vitepress.dev/reference/site-config
export default withMermaid(defineConfig({
  title: 'Ignite',
  description: 'Developer documentation for Ignite, a Laravel + Vue goal tracking app',
  cleanUrls: true,
  srcExclude: ['superpowers/**', 'tmp/**', 'use-containerized-services/**'],
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/getting-started' },
      { text: 'Features', link: '/features/authentication' },
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
      {
        text: 'Configuration',
        items: [
          { text: 'Configuration', link: '/configuration' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Authentication', link: '/features/authentication' },
          { text: 'Goal Types', link: '/features/goal-types' },
          { text: 'Categories', link: '/features/categories' },
          { text: 'Streaks', link: '/features/streaks' },
          { text: 'Milestones', link: '/features/milestones' },
          { text: 'Internationalization', link: '/features/internationalization' },
          { text: 'Admin Panel', link: '/features/admin-panel' },
          { text: 'Feedback & Ops', link: '/features/feedback-and-ops' },
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
    socialLinks: [
      { icon: 'github', link: 'https://github.com/Promethys/ignite' },
    ],
  },
}))
