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

        if (self::$lastTestClass !== get_class($this)) {
            $this->refreshTestDatabase();
            self::$lastTestClass = get_class($this);
        }
    }

    protected function refreshTestDatabase(): void
    {
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);
    }
}
