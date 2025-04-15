<?php

namespace nickwelsh\Fennel;

use nickwelsh\Fennel\Services\FennelService;
use nickwelsh\Fennel\View\Components\Image;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FennelServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(FennelService::class, function () {
            return new FennelService;
        });
    }

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
            ->hasRoute('web')
            ->hasViews('fennel')
            ->hasViewComponents('fennel', Image::class);

    }
}
