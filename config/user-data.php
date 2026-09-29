<?php

return [
    'master_key' => env('USER_DATA_MASTER_KEY'),

    'previous_master_keys' => [
        ...array_filter(
            explode(',', env('USER_DATA_PREVIOUS_MASTER_KEYS', ''))
        ),
    ],

    'key_connection' => env('USER_DATA_KEYS_DB_URL') ? 'keys' : null,
];
