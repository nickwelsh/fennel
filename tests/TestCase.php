<?php

namespace nickwelsh\Fennel\Tests;

use nickwelsh\Fennel\FennelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            FennelServiceProvider::class,
        ];
    }
}
