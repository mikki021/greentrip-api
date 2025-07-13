<?php

namespace App\Providers;

use App\Contracts\FlightProviderInterface;
use App\Services\AuthService;
use App\Services\FakeFlightProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Auth rate limiter (5 attempts per minute)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // General API rate limiter (60 requests per minute)
        RateLimiter::for('general', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Search rate limiter (30 searches per minute)
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Emissions rate limiter (100 queries per minute)
        RateLimiter::for('emissions', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // API rate limiter (default for API routes)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
