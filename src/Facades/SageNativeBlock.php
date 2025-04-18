<?php

namespace Imagewize\SageNativeBlock\Facades;

use Illuminate\Support\Facades\Facade;

class SageNativeBlock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'SageNativeBlock';
    }
}
