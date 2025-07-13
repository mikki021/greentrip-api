<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HealthControllerTest extends TestCase
{
    /**
     * Test that the health endpoint returns a successful response
     */
    public function test_health_endpoint_returns_successful_response()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version',
                    'environment',
                    'checks' => [
                        'database',
                        'cache',
                        'storage',
                        'memory'
                    ]
                ]);
    }

    /**
     * Test that the health endpoint returns healthy status when all systems are working
     */
    public function test_health_endpoint_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'healthy'
                ]);
    }

    /**
     * Test that database check returns healthy status
     */
    public function test_database_check_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonPath('checks.database.status', 'healthy')
                ->assertJsonPath('checks.database.connection', 'mysql_testing')
                ->assertJsonStructure([
                    'checks' => [
                        'database' => [
                            'status',
                            'connection',
                            'response_time_ms',
                            'message'
                        ]
                    ]
                ]);
    }

    /**
     * Test that cache check returns healthy status
     */
    public function test_cache_check_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonPath('checks.cache.status', 'healthy')
                ->assertJsonStructure([
                    'checks' => [
                        'cache' => [
                            'status',
                            'driver',
                            'response_time_ms',
                            'message'
                        ]
                    ]
                ]);
    }

    /**
     * Test that storage check returns healthy status
     */
    public function test_storage_check_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonPath('checks.storage.status', 'healthy')
                ->assertJsonStructure([
                    'checks' => [
                        'storage' => [
                            'status',
                            'driver',
                            'response_time_ms',
                            'message'
                        ]
                    ]
                ]);
    }

    /**
     * Test that memory check returns healthy status
     */
    public function test_memory_check_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonPath('checks.memory.status', 'healthy')
                ->assertJsonStructure([
                    'checks' => [
                        'memory' => [
                            'status',
                            'memory_limit',
                            'memory_usage',
                            'peak_memory_usage',
                            'memory_usage_percent',
                            'message'
                        ]
                    ]
                ]);
    }

    /**
     * Test that health endpoint is accessible without authentication
     */
    public function test_health_endpoint_does_not_require_authentication()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    /**
     * Test that health endpoint returns correct version
     */
    public function test_health_endpoint_returns_correct_version()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJson([
                    'version' => '1.0.0'
                ]);
    }

    /**
     * Test that health endpoint returns correct environment
     */
    public function test_health_endpoint_returns_correct_environment()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJson([
                    'environment' => 'local'
                ]);
    }

    /**
     * Test that health endpoint returns valid timestamp
     */
    public function test_health_endpoint_returns_valid_timestamp()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'timestamp'
                ]);

        $timestamp = $response->json('timestamp');
        $this->assertIsString($timestamp);
        $this->assertNotEmpty($timestamp);
    }

    /**
     * Test that response times are reasonable
     */
    public function test_response_times_are_reasonable()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $databaseResponseTime = $response->json('checks.database.response_time_ms');
        $cacheResponseTime = $response->json('checks.cache.response_time_ms');
        $storageResponseTime = $response->json('checks.storage.response_time_ms');

        // Response times should be reasonable (less than 5 seconds)
        $this->assertLessThan(5000, $databaseResponseTime);
        $this->assertLessThan(5000, $cacheResponseTime);
        $this->assertLessThan(5000, $storageResponseTime);
    }
}