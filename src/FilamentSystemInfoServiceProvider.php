<?php

declare(strict_types=1);

namespace Michael4d45\FilamentSystemInfo;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentSystemInfoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-system-info')
            ->hasConfigFile('filament-system-info');
    }
}