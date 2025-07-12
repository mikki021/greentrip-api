<?php

namespace App\Providers;

use App\Contracts\FlightProviderInterface;
use App\Services\AuthService;
use App\Services\FakeFlightProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });

        $this->app->bind(FlightProviderInterface::class, FakeFlightProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
