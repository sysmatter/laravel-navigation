<?php

namespace SysMatter\Navigation\Facades;

use Illuminate\Support\Facades\Facade;

class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }
}
