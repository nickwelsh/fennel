<?php

namespace nickwelsh\Fennel\Facades;

use Illuminate\Support\Facades\Facade;
use nickwelsh\Fennel\Services\FennelService;

/**
 * @see FennelService
 */
class Fennel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FennelService::class;
    }
}
