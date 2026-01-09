# Filament System Info Widget

A Filament widget for displaying system information including package versions, PHP version, and deployment details with update notifications.

## Installation

Install the package via Composer:

```bash
composer require michael4d45/filament-system-info-widget
```

## Basic Usage

Add the widget to your Filament dashboard:

```php
use Michael4d45\FilamentSystemInfo\Widgets\SystemInfoWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            SystemInfoWidget::class,
        ];
    }
}
```

## Configuration

### Using Configuration Methods

You can configure the widget using fluent methods:

```php
SystemInfoWidget::make()
    ->heading('My System Info')
    ->packages([
        [
            'name' => 'laravel/framework',
            'displayName' => 'Laravel',
            'icon' => 'heroicon-o-cpu-chip',
            'type' => 'packagist',
        ],
        [
            'name' => 'php',
            'displayName' => 'PHP',
            'icon' => 'heroicon-o-code-bracket',
            'type' => 'php',
        ],
    ])
    ->showDeploymentInfo(true)
    ->pollingInterval('30s')
```

### Using the Configure Method

For more advanced configuration:

```php
SystemInfoWidget::make()
    ->configure([
        'packages' => [
            [
                'name' => 'laravel/framework',
                'displayName' => 'Laravel Framework',
                'icon' => 'heroicon-o-cpu-chip',
                'type' => 'packagist',
            ],
            [
                'name' => 'filament/filament',
                'displayName' => 'Filament Admin',
                'icon' => 'heroicon-o-squares-2x2',
                'type' => 'packagist',
            ],
        ],
        'heading' => 'System Overview',
        'pollingInterval' => '30s',
        'showDeploymentInfo' => true,
        'releaseInfoPath' => '.release-info',
    ])
```

### Publishing Configuration

You can publish the configuration file to customize defaults:

```bash
php artisan vendor:publish --tag=filament-system-info-config
```

Then edit `config/filament-system-info.php`:

```php
return [
    'packages' => [
        [
            'name' => 'laravel/framework',
            'displayName' => 'Laravel',
            'icon' => 'heroicon-o-cpu-chip',
            'type' => 'packagist',
        ],
        // Add your own packages...
    ],
    'show_deployment_info' => true,
    'heading' => 'System Information',
    'polling_interval' => '60s',
];
```

## Package Types

The widget supports two types of packages:

- **`packagist`**: Composer packages that can be checked for updates via Packagist API
- **`php`**: PHP version (automatically detected)

## Deployment Information

The widget automatically shows deployment information by:

1. **Git**: Reading the last commit message and timestamp from `git log`
2. **Fallback**: Reading from a `.release-info` file (format: `message|timestamp`)

The deployment info can be disabled:

```php
SystemInfoWidget::make()
    ->showDeploymentInfo(false)
```

## Features

- **Version Monitoring**: Tracks current versions of configured packages
- **Update Notifications**: Highlights packages with available updates
- **Deployment Tracking**: Shows last deployment time and commit message
- **Configurable**: Customize which packages to monitor and display settings
- **Auto-polling**: Refreshes data at configurable intervals
- **Production Ready**: Graceful fallbacks when git/data is unavailable

## Package Structure

```
├── config/
│   └── filament-system-info.php
├── src/
│   ├── FilamentSystemInfoServiceProvider.php
│   └── Widgets/
│       └── SystemInfoWidget.php
├── composer.json
└── README.md
```

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Filament 3.0+

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).