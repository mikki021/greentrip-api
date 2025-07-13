<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private static $lastTestClass = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql_testing']);
        $this->app['config']->set('database.default', 'mysql_testing');

        // Disable rate limiting middleware for tests by default
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        if (self::$lastTestClass !== get_class($this)) {
            $this->refreshTestDatabase();
            self::$lastTestClass = get_class($this);
        }
    }

    protected function refreshTestDatabase(): void
    {
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);
    }

    /**
     * Enable rate limiting for specific tests
     * Use this when you want to test rate limiting behavior
     */
    protected function withRateLimiting(): self
    {
        // Remove the middleware exclusion to enable rate limiting
        $this->withoutMiddleware([]);

        return $this;
    }

    /**
     * Test rate limiting with custom limits
     * Use this to test specific rate limiting scenarios
     */
    protected function withCustomRateLimit(string $limit): self
    {
        // Temporarily override the rate limit for testing
        config(['auth.api_rate_limit.auth' => $limit]);

        return $this;
    }
}
