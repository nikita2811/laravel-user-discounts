<?php

namespace Nikita\LaravelUserDiscounts\Providers;

use Illuminate\Support\ServiceProvider;
use Nikita\LaravelUserDiscounts\Contracts\DiscountManagerContract;
use Nikita\LaravelUserDiscounts\Services\DiscountManager;


class UserDiscountServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(DiscountManagerContract::class, DiscountManager::class);
        $this->app->alias(DiscountContract::class, 'discounts');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
