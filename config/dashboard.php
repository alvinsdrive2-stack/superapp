<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Data Sources Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your data sources here. You can add new data sources without
    | modifying the application code.
    |
    */
    'data_sources' => [
        [
            'class' => \App\Services\Dashboard\DataSources\BalaiDataSource::class,
            'connection' => 'mysql_balai',
            'table' => 'data_pencatatans',
            'identifier' => 'balai',
            'display_name' => 'Balai',
            'color' => '#4F46E5'
        ],
        [
            'class' => \App\Services\Dashboard\DataSources\RegulerDataSource::class,
            'connection' => 'mysql_reguler',
            'table' => 'data_pencatatans',
            'identifier' => 'reguler',
            'display_name' => 'Reguler',
            'color' => '#10B981'
        ],
        [
            'class' => \App\Services\Dashboard\DataSources\FGSuiseiDataSource::class,
            'connection' => 'mysql_fg',
            'table' => 'data_pencatatans',
            'identifier' => 'fg_suisei',
            'display_name' => 'FG/Suisei',
            'color' => '#F59E0B'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default_duration' => 300, // 5 minutes
        'prefix' => 'dashboard',
        'tags' => ['dashboard', 'analytics']
    ],

    /*
    |--------------------------------------------------------------------------
    | Time Series Settings
    |--------------------------------------------------------------------------
    */
    'time_series' => [
        'default_period' => '6_months',
        'max_periods' => 24, // Maximum months to fetch
        'forecast_periods' => 3,
        'anomaly_threshold' => 2.0
    ],

    /*
    |--------------------------------------------------------------------------
    | Province Settings
    |--------------------------------------------------------------------------
    */
    'provinces' => [
        'default_limit' => 10,
        'top_provinces_for_chart' => 10,
        'top_provinces_for_cards' => 8,
        'top_provinces_for_progress' => 3
    ],

    /*
    |--------------------------------------------------------------------------
    | Chart Configuration
    |--------------------------------------------------------------------------
    */
    'charts' => [
        'default_theme' => 'light',
        'animation_duration' => 800,
        'responsive' => true,
        'maintain_aspect_ratio' => false
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Settings
    |--------------------------------------------------------------------------
    |
    | Automatically discover new databases that follow the naming convention.
    | For example: mysql_new_system, mysql_extra_data, etc.
    |
    */
    'auto_discovery' => [
        'enabled' => true,
        'connection_pattern' => 'mysql_%',
        'exclude' => ['mysql', 'mysql_testing'],
        'default_table' => 'data_pencatatans'
    ]
];