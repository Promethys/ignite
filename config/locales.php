<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The single source of truth for the locales the application ships.
    | Each entry maps a locale code to its human-readable display name.
    | The locale middleware, the guest switcher, and the settings selector
    | all read this map. English is the default and fallback.
    |
    */

    'supported' => [
        'en' => 'English',
        'fr' => 'Français',
    ],

    /*
    |--------------------------------------------------------------------------
    | Open Graph Locales
    |--------------------------------------------------------------------------
    |
    | Open Graph expects a language_TERRITORY code rather than the bare
    | language code used everywhere else, so each supported locale maps to
    | the territory variant advertised to social scrapers.
    |
    */

    'open_graph' => [
        'en' => 'en_US',
        'fr' => 'fr_FR',
    ],

];
