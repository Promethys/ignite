<?php

return [
    'head' => 'Dashboard',
    'breadcrumb' => 'Dashboard',
    'title' => 'Welcome back!',
    'subtitle' => 'Your momentum at a glance.',
    'stats' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'completion' => 'Completion',
        'total' => 'Total',
    ],
    'active_goals' => 'Active goals',
    'charts' => [
        'completions' => [
            'title' => 'Monthly completed goals',
            'series' => 'Completed goals',
            'empty' => [
                'title' => 'No completed goals yet',
                'description' => 'Your completions over the last 12 months will appear here.',
            ],
        ],
        'categories' => [
            'title' => 'Active categories',
            'total' => 'Total',
            'empty' => [
                'title' => 'No active goals',
                'description' => 'Assign categories to your goals to see a breakdown here.',
            ],
        ],
    ],
    'empty' => [
        'title' => 'No active goals',
        'description' => "It's cold up here...",
    ],
];
