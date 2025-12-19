<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSO Systems Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all systems connected to SSO
    |
    */

    'systems' => [
        'balai' => [
            'name' => 'Sistem Balai',
            'display_name' => 'BALAI System',
            'url' => env('BALAI_URL', 'https://balai.lsp-gatensi.id'),
            'api_url' => env('BALAI_API_URL', 'https://balai.lsp-gatensi.id/api'),
            'connection' => 'mysql_balai'
        ],
        'reguler' => [
            'name' => 'Sistem Reguler',
            'display_name' => 'REGULER System',
            'url' => env('REGULER_URL', 'https://reguler.lsp-gatensi.id'),
            'api_url' => env('REGULER_API_URL', 'https://reguler.lsp-gatensi.id/api'),
            'connection' => 'mysql_reguler'
        ],
        'fg' => [
            'name' => 'Sistem FG/Suisei',
            'display_name' => 'FG System',
            'url' => env('FG_URL', 'https://fg.lsp-gatensi.id'),
            'api_url' => env('FG_API_URL', 'https://fg.lsp-gatensi.id/api'),
            'connection' => 'mysql_fg'
        ],
        'tuk' => [
            'name' => 'Sistem TUK',
            'display_name' => 'TUK System',
            'url' => env('TUK_URL', 'https://tuk.lsp-gatensi.id'),
            'api_url' => env('TUK_API_URL', 'https://tuk.lsp-gatensi.id/api'),
            'connection' => 'mysql_tuk'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Systems List
    |--------------------------------------------------------------------------
    |
    | Simple array of available system keys
    |
    */
    'available_systems' => ['balai', 'reguler', 'fg', 'tuk']
];