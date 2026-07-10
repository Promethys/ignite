<?php

return [
    'title' => 'Goals',
    'subtitle' => '{1}:count goal · :active active|[2,*]:count goals · :active active',

    'breadcrumb' => [
        'index' => 'Goals',
        'create' => 'Create',
        'edit' => 'Edit',
        'all_entries' => 'All Entries',
    ],

    'head' => [
        'index' => 'Goals',
        'create' => 'New goal',
        'entries' => 'Goal Entries',
    ],

    'actions' => [
        'new' => 'New goal',
        'define' => 'Define a Goal',
        'log_progress' => 'Log progress',
        'mark_completed' => 'Mark as completed',
        'pause' => 'Pause',
        'resume' => 'Resume',
    ],

    'filters' => [
        'search_placeholder' => 'Search goals...',
        'category' => 'Category',
        'all_categories' => 'All categories',
        'no_category' => 'No category',
        'status' => 'Status',
        'all' => 'All',
    ],

    'empty' => [
        'none_title' => 'No Goal Yet',
        'none_description' => "You don't have any goal yet. Get started by creating your first goal.",
        'no_match_title' => 'No Goal found',
        'no_match_description' => 'No goals match this search or filter.',
    ],

    'delete' => [
        'description' => 'This action cannot be undone. This will permanently delete your goal.',
    ],

    'types' => [
        'simple' => 'Simple',
        'quantifiable' => 'Quantifiable',
        'recurring' => 'Recurring',
        'multi_step' => 'Multi Step',
    ],
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],
    'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'paused' => 'Paused',
        'abandoned' => 'Abandoned',
    ],
    'recurrences' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'annually' => 'Annually',
    ],
    'directions' => [
        'ascending' => 'Ascending',
        'descending' => 'Descending',
    ],
    'polarities' => [
        'positive' => 'Positive',
        'negative' => 'Negative',
    ],

    'badges' => [
        'priority' => ':level Priority',
    ],

    'form' => [
        'create_title' => 'Create a goal',
        'create_description' => 'Create your new goal',
        'edit_title' => 'Edit a goal',
        'edit_description' => 'Edit your goal',
        'title' => 'Title',
        'category' => 'Category',
        'create_category' => 'Create a category',
        'select_category' => 'Select a category',
        'type' => 'Type',
        'select_type' => 'Select a goal type',
        'description' => 'Description',
        'current_value' => 'Current value',
        'target_value' => 'Target value',
        'unit' => 'Unit',
        'unit_placeholder' => 'km, books, sessions, etc.',
        'start_date' => 'Start date',
        'deadline' => 'Deadline',
        'completed_at' => 'Completed at',
        'priority' => 'Priority',
        'select_priority' => 'Select a goal priority',
        'status' => 'Status',
        'select_status' => 'Select a status',
        'points' => 'Points',
        'direction' => 'Direction',
        'direction_help' => "Choose if your goal's evolution will be ascending or descending",
        'select_direction' => 'Select a direction',
        'polarity' => 'Polarity',
        'polarity_help' => 'Controls how the streak is counted. Positive counts consecutive periods where you log progress, for building a habit. Negative counts how long you go without an entry, for breaking one. Only applies to recurring goals.',
        'select_polarity' => 'Select a polarity',
        'recurrence' => 'Recurrence',
        'select_recurrence' => 'Select a recurrence',
        'submit_create' => 'Create',
        'submit_edit' => 'Edit',
    ],

    'show' => [
        'log_progress_title' => 'Log progress',
        'log_progress_description' => 'Add an entry to track your progress on this goal.',
        'progress_value' => 'Progress value',
        'progress_value_placeholder' => '25',
        'note' => 'Note (optional)',
        'note_placeholder' => 'Good progress today...',
        'logging' => 'Logging...',
        'progress_over_time' => 'Progress over time',
        'no_chart_data' => 'No progress data yet. Log your first entry to see the chart.',
        'steps' => 'Steps',
        'milestones' => 'Milestones',
        'milestones_progress' => ':done of :total completed',
        'no_milestones' => 'No milestones yet. Add checkpoints to break this goal into steps.',
        'about' => 'About',
        'about_category' => 'Category',
        'about_started' => 'Started',
        'about_deadline' => 'Deadline',
        'about_direction' => 'Direction',
        'about_recurrence' => 'Recurrence',
        'about_priority' => 'Priority',
        'recent_entries' => 'Recent entries',
        'no_entries' => 'No entries yet. Log your first entry to get started.',
        'view_all' => '{1}View all :count entry|[2,*]View all :count entries',
        'simple_completed_on' => 'Completed on :date',
        'simple_prompt' => "Mark this goal complete when you're done.",
    ],

    'summary' => [
        'status' => 'Status',
        'progress' => 'Progress',
        'target' => 'Target',
        'entries_logged' => 'Entries logged',
        'steps_done' => 'Steps done',
        'priority' => 'Priority',
        'recurrence' => 'Recurrence',
        'started' => 'Started',
        'until_deadline' => 'Until deadline',
        'past_deadline' => 'Past deadline',
        'no_deadline' => 'No deadline',
        'overdue' => 'Overdue',
        'days' => '{1}:count day|[2,*]:count days',
    ],

    'progress' => [
        'steps' => '{1}:done / :total step|[2,*]:done / :total steps',
    ],

    'chart' => [
        'values' => 'Values',
        'target' => 'Target Value',
        'entry_date' => 'Entry date',
    ],

    'streak' => [
        'title' => 'Streak',
        'none' => 'No active streak',
        'longest_label' => 'Longest:',
        'positive' => [
            'day' => '{1}:count-day streak|[2,*]:count-day streak',
            'week' => '{1}:count-week streak|[2,*]:count-week streak',
            'month' => '{1}:count-month streak|[2,*]:count-month streak',
            'year' => '{1}:count-year streak|[2,*]:count-year streak',
        ],
        'negative' => [
            'day' => '{1}:count day without a relapse|[2,*]:count days without a relapse',
            'week' => '{1}:count week without a relapse|[2,*]:count weeks without a relapse',
            'month' => '{1}:count month without a relapse|[2,*]:count months without a relapse',
            'year' => '{1}:count year without a relapse|[2,*]:count years without a relapse',
        ],
        'deadline_progress' => 'Day :elapsed of :total to deadline',
    ],

    'entries' => [
        'title' => 'All Entries',
        'search_placeholder' => 'Search...',
        'pick_date' => 'Pick a date',
        'clear_filters' => 'Clear filters',
        'load_previous' => 'Load previous',
        'load_more' => 'Load more',
        'delete_title' => 'Delete entry',
        'delete_description' => 'Delete that entry from progress history?',
        'no_result' => 'No result found.',
    ],
];
