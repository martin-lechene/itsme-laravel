<?php

namespace ItsmeLaravel\Itsme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizationUrl()
 * @method static array handleCallback(\Illuminate\Http\Request $request)
 *
 * @see \ItsmeLaravel\Itsme\Services\ItsmeService
 */
class Itsme extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \ItsmeLaravel\Itsme\Services\ItsmeService::class;
    }
}

