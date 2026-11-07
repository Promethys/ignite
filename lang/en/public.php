<?php

return [
    'skip_to_content' => 'Skip to content',
    'language_switcher' => 'Change language, current: :language',
    'nav' => [
        'home' => 'Home',
        'close_menu' => 'Close menu',
        'main' => 'Main navigation',
        'features' => 'Features',
        'docs' => 'Docs',
        'self_host' => 'Self-host',
        'github' => 'GitHub',
        'open_menu' => 'Open menu',
        'menu' => 'Menu',
        'developer_docs' => 'Developer docs',
        'releases' => 'Releases',
        'report_bug' => 'Report a bug',
        'privacy' => 'Privacy',
        'terms' => 'Terms',
        'contact' => 'Contact',
    ],
    'footer' => [
        'description' => 'Track any goal with visual progress, milestones and streaks.',
        'product' => 'Product',
        'project' => 'Project',
        'legal' => 'Legal',
        'license' => 'Source available under FSL-1.1-MIT.',
    ],
    'link_to' => 'Link to :title',
    'read_docs' => 'Read the docs',
    'on_this_page' => 'On this page',
    'intro' => [
        'label' => 'Features',
        'title' => 'Everything Ignite does, in one page.',
        'lead' => 'Four goal types, progress you can see, streaks that respect your calendar, and an assistant that works on your own key.',
    ],
    'self_host' => [
        'onward' => 'The installation guide covers the setup and configuration you need.',
        'link' => 'Read the self-hosting guide',
        'title' => 'Self-host Ignite',
        'lead' => 'Run Ignite on your own infrastructure.',
    ],
    'privacy' => [
        'onward' => 'Read how Ignite encrypts your personal data while this policy is being prepared.',
        'link' => 'Read about encryption',
        'title' => 'Privacy',
        'lead' => 'How Ignite handles your data.',
    ],
    'terms' => [
        'onward' => 'These terms are being prepared. Contact us with any questions about using Ignite.',
        'link' => 'Contact support',
        'title' => 'Terms of use',
        'lead' => 'The terms for using Ignite.',
    ],
    'coming_soon' => 'This page is coming soon.',
    'features' => [
        'goal-types' => [
            'title' => 'Four goal types',
            'description' => 'Pick the shape that fits the goal, not the other way round.',
            'points' => [
                'Simple: done or not done.',
                'Quantifiable: a number toward a target, with a unit, like :current / :target :unit.',
                'Recurring: a habit checked in daily, weekly, monthly or annually.',
                'Multi-step: an ordered list of steps, each with an optional deadline.',
            ],
        ],
        'progress' => [
            'title' => 'Progress you can see',
            'description' => 'See progress at a glance. Quantifiable goals get a chart over time with the target line, and the dashboard shows completions per month and goals per category.',
        ],
        'streaks' => [
            'title' => 'Streaks and the heatmap',
            'description' => 'Recurring goals show your current and longest streak and an activity heatmap. Streaks are computed in your own timezone, so a late check-in still counts for the right day.',
        ],
        'milestones' => [
            'title' => 'Milestones and steps',
            'description' => 'Set checkpoints at a value on quantifiable goals. Break multi-step goals into steps, and reorder them by drag and drop or with the keyboard.',
        ],
        'assistant' => [
            'title' => 'An assistant on your own key',
            'description' => 'A side panel that can read and update your goals. Bring a key from Anthropic, OpenAI, Gemini, Mistral, DeepSeek, Groq, xAI or OpenRouter. Deleting anything asks you to confirm first.',
            'points' => [
                'Your provider may charge you for what you use.',
                'Your key is stored encrypted.',
            ],
        ],
        'mcp' => [
            'title' => 'Your goals in your AI client',
            'description' => 'A built-in MCP server with 23 tools lets Claude, Cursor or any MCP client list, create and update your goals, log progress and check in. It works over a token you create and can revoke.',
        ],
        'make-it-yours' => [
            'title' => 'Make it yours',
            'description' => 'Ten categories are created when you register, each with a colour. Rename or replace them. Pick your own main colour for the app, with up to five themes and an optional colour for dark mode. Ignite is in English and French, emails included.',
        ],
        'your-data' => [
            'title' => 'Your data',
            'description' => 'Goal titles, descriptions, notes, category names and AI keys are encrypted under a key that belongs to you alone. The admin panel never shows them. Deleting your account destroys that key.',
            'points' => [
                'Sign in with email and password, Google or GitHub.',
                'Two-factor authentication.',
                'Delete your account from the settings.',
            ],
        ],
    ],
    'faq' => [
        'label' => 'Questions',
        'title' => 'Short answers',
        'items' => [
            'free' => [
                'question' => 'Is Ignite free?',
                'answer' => 'Ignite is free to use today.',
            ],
            'assistant-cost' => [
                'question' => 'What does the assistant cost me?',
                'answer' => 'The assistant runs on your own key. Ignite does not charge for it, but your AI provider may charge you for what you use, under their own pricing.',
            ],
            'mcp-clients' => [
                'question' => 'Which AI clients work with the MCP server?',
                'answer' => 'Claude, Cursor and any other client that supports MCP. You create a token in Ignite, paste it in your client, and can revoke it at any time.',
            ],
            'self-host' => [
                'question' => 'Can I run my own copy?',
                'answer' => 'Yes. The code is on GitHub and ships with a Docker setup.',
            ],
            'delete' => [
                'question' => 'What happens to my data when I delete my account?',
                'answer' => 'Your account and goals are deleted, and the key that encrypts your text is destroyed with them, so that text can no longer be read.',
            ],
            'open-source' => [
                'question' => 'Is Ignite open source?',
                'answer' => 'It is source available, under FSL-1.1-MIT: you can read the code and run your own copy, and each release becomes MIT-licensed two years after it is published.',
            ],
        ],
    ],
    'cta' => [
        'title' => 'Track your first goal.',
        'code' => 'Read the code',
    ],
    'showcase' => [
        'distance_axis' => 'Distance (km)',
        'encrypted' => 'Encrypted with your key',
        'encrypted_goals' => 'Goal titles and descriptions',
        'encrypted_notes' => 'Progress notes',
        'encrypted_categories' => 'Category names',
        'encrypted_keys' => 'Assistant API keys',
        'simple' => 'Send the application',
        'quantifiable' => 'Half marathon training',
        'recurring' => 'Read every day',
        'multi_step' => 'Launch a personal website',
        'completed' => 'Done',
        'step_1' => 'Define the content',
        'step_2' => 'Build the pages',
        'step_3' => 'Publish the website',
        'assistant_prompt' => 'Log 5 km on my half marathon and delete the old 10K goal',
        'assistant_reply' => 'Logged 5 km. You\'re at :current / :target km.',
        'assistant_alt' => 'The Ignite assistant panel beside the goal dashboard',
        'themes_alt' => 'Ignite appearance settings showing custom colour themes',
    ],
    'terminal' => [
        'client' => 'AI client',
        'prompt' => 'Log 12 km on my half marathon goal',
        'result' => 'Logged 12 km. :current / :target :unit (:progress%)',
        'reply' => 'Done. You are at :current of :target :unit on your half marathon goal.',
        'replay' => 'Replay',
    ],
];
