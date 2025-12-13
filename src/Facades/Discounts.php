<?php

namespace Nikita\LaravelUserDiscounts\Facades;

use Illuminate\Support\Facades\Facade;


class Discounts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'discounts';
    }
}
