<?php

return [
    'head' => 'Categories',
    'breadcrumb' => 'Categories',
    'title' => 'Categories',
    'subtitle' => '{1}:count category|[2,*]:count categories',
    'trigger' => 'Category',
    'new' => 'New category',

    'empty' => [
        'title' => 'No Category Yet',
        'description' => "You don't have any category yet. Create one to organize your goals.",
    ],

    'delete' => [
        'description' => 'This action cannot be undone. This will permanently delete your category.',
    ],

    'counts' => [
        'goals' => 'goals',
        'active' => 'active',
        'done' => 'done',
    ],

    'form' => [
        'create_title' => 'Create a category',
        'create_description' => 'Create a category here. You can use it to organize your goals.',
        'edit_title' => 'Edit a category',
        'edit_description' => 'Edit your category.',
        'name' => 'Name',
        'name_placeholder' => 'Sports',
        'description' => 'Description',
        'description_placeholder' => 'All sportive goals like soccer, tennis, gym, ...',
        'color' => 'Color',
        'icon' => 'Icon',
        'submit_create' => 'Create',
        'submit_edit' => 'Edit',
    ],
];
