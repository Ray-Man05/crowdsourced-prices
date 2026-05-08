<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\PriceAggregator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void
    {
        $this->app->singleton(PriceAggregator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
