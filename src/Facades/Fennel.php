<?php

namespace nickwelsh\Fennel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \nickwelsh\Fennel\Fennel
 */
class Fennel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \nickwelsh\Fennel\Fennel::class;
    }
}
