<?php

namespace nickwelsh\Fennel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use nickwelsh\Fennel\Commands\FennelCommand;

class FennelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('fennel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_fennel_table')
            ->hasCommand(FennelCommand::class);
    }
}
