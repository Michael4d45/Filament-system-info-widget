<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Widget Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the default settings for the SystemInfoWidget.
    | These can be overridden when using the widget in your Filament panels.
    |
    */

    'packages' => [
        [
            'name' => 'laravel/framework',
            'displayName' => 'Laravel Version',
            'icon' => 'heroicon-o-cpu-chip',
            'type' => 'packagist',
        ],
        [
            'name' => 'php',
            'displayName' => 'PHP Version',
            'icon' => 'heroicon-o-code-bracket',
            'type' => 'php',
        ],
        [
            'name' => 'filament/filament',
            'displayName' => 'Filament Version',
            'icon' => 'heroicon-o-squares-2x2',
            'type' => 'packagist',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Information
    |--------------------------------------------------------------------------
    |
    | Configure how deployment information is displayed.
    |
    */

    'show_deployment_info' => true,
    'release_info_path' => '.release-info',

    /*
    |--------------------------------------------------------------------------
    | Widget Settings
    |--------------------------------------------------------------------------
    |
    | Default widget settings.
    |
    */

    'heading' => 'System Information',
    'polling_interval' => '60s',
];