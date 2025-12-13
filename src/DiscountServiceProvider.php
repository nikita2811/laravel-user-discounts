<?php

namespace Nikita\LaravelUserDiscounts;

use Illuminate\Support\ServiceProvider;
use Nikita\LaravelUserDiscounts\Contracts\DiscountManagerContract;
use Nikita\LaravelUserDiscounts\Services\DiscountManager;


class DiscountServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(DiscountManagerContract::class, DiscountManager::class);
        $this->app->alias(DiscountManagerContract::class, 'discounts');

        // Merge package config with app config
        $this->mergeConfigFrom(__DIR__ . '/../config/discounts.php', 'discounts');
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'discounts-migrations');

        $this->publishes([
            __DIR__ . '/../config/discounts.php' => config_path('discounts.php'),
        ], 'config');
    }
}
