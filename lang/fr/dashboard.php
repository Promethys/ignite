<?php

return [
    'head' => 'Tableau de bord',
    'breadcrumb' => 'Tableau de bord',
    'title' => 'Bon retour !',
    'subtitle' => 'Votre élan en un coup d\'œil.',
    'stats' => [
        'active' => 'Actifs',
        'completed' => 'Terminés',
        'completion' => 'Achèvement',
        'total' => 'Total',
    ],
    'active_goals' => 'Objectifs actifs',
    'charts' => [
        'completions' => [
            'title' => 'Objectifs terminés par mois',
            'series' => 'Objectifs terminés',
            'empty' => [
                'title' => 'Aucun objectif terminé pour le moment',
                'description' => 'Vos objectifs terminés ces 12 derniers mois apparaîtront ici.',
            ],
        ],
        'categories' => [
            'title' => 'Catégories actives',
            'total' => 'Total',
            'empty' => [
                'title' => 'Aucun objectif actif',
                'description' => 'Attribuez des catégories à vos objectifs pour voir une répartition ici.',
            ],
        ],
    ],
    'empty' => [
        'title' => 'Aucun objectif actif',
        'description' => 'Il fait froid par ici...',
    ],
];
