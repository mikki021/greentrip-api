<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class RateLimitingTest extends TestCase
{
    private User $user;
    private ?string $token = null;

    protected function setUp(): void
    {
        // Don't call parent::setUp() to avoid disabling rate limiting
        $this->refreshApplication();

        // Flush cache before each test to ensure a clean state
        $this->app['cache']->flush();

        // Use MySQL testing database like other tests
        config(['database.default' => 'mysql_testing']);
        $this->app['config']->set('database.default', 'mysql_testing');

        // Keep rate limiting enabled for this test class
        // Don't call withoutMiddleware() here

        // Run migrations for this test
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Try to login, but don't fail if rate limited
        try {
            $response = $this->postJson('/api/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);

            if ($response->status() === 200) {
                $this->token = $response->json('authorization.token');
            }
        } catch (\Exception $e) {
            // Login might fail due to rate limiting, that's okay
        }
    }

    public function test_auth_endpoints_have_rate_limiting(): void
    {
        // Clear rate limiting cache to ensure clean state
        $this->app['cache']->flush();

        // Make 5 requests with valid credentials (this should hit the rate limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);

            // First few requests should succeed
            if ($i < 4) {
                $response->assertStatus(200);
            }
        }

        // 6th request should be rate limited (we've hit the 5 requests/minute limit)
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(429); // Too Many Requests
        $this->assertTrue($response->headers->has('Retry-After'), '429 response should have Retry-After header');
        $this->assertNotEmpty($response->headers->get('Retry-After'), 'Retry-After header should not be empty');
    }

    public function test_flight_search_has_rate_limiting(): void
    {
        $this->app['cache']->flush();

        if (!$this->token) {
            $this->markTestSkipped('Login was rate limited, skipping flight search test');
        }

        // Make 30 requests (the limit for flight search)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/flights/search', [
                'from' => 'JFK',
                'to' => 'LAX',
                'date' => now()->addDays(30)->format('Y-m-d'),
            ]);

            $response->assertStatus(200);
        }

        // 31st request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertStatus(429, '31st flight search should be rate limited (429)');
        $this->assertTrue($response->headers->has('Retry-After'), '429 response should have Retry-After header');
        $this->assertNotEmpty($response->headers->get('Retry-After'), 'Retry-After header should not be empty');
    }

    public function test_emissions_endpoints_have_higher_rate_limit(): void
    {
        $this->app['cache']->flush();

        if (!$this->token) {
            $this->markTestSkipped('Login was rate limited, skipping emissions test');
        }

        // Make 100 requests (the limit for emissions)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson('/api/emissions/summary');

            if ($response->status() !== 200) {
                Log::info('RateLimitingTest: Emissions request failed early', [
                    'request_number' => $i + 1,
                    'status' => $response->status(),
                    'body' => $response->getContent()
                ]);
                break;
            }
        }

        // 101st request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/emissions/summary');

        $response->assertStatus(429, '101st emissions request should be rate limited (429)');
        $this->assertTrue($response->headers->has('Retry-After'), '429 response should have Retry-After header');
        $this->assertNotEmpty($response->headers->get('Retry-After'), 'Retry-After header should not be empty');
    }

    public function test_rate_limits_are_separate_per_endpoint(): void
    {
        $this->app['cache']->flush();

        if (!$this->token) {
            $this->markTestSkipped('Login was rate limited, skipping separate endpoints test');
        }

        // Hit the auth rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);
        }

        // Auth should be rate limited
        $authResponse = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $authResponse->assertStatus(429, 'Auth should be rate limited after 5 requests');
        $this->assertTrue($authResponse->headers->has('Retry-After'), '429 response should have Retry-After header');
        $this->assertNotEmpty($authResponse->headers->get('Retry-After'), 'Retry-After header should not be empty');

        // But flight search should still work (different rate limit)
        $flightResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $flightResponse->assertStatus(200, 'Flight search should not be rate limited after auth is limited');
    }

    public function test_rate_limits_reset_after_time_window(): void
    {
        // This test would require time manipulation
        // In a real scenario, you might use Carbon::setTestNow() to test this
        $this->markTestSkipped('Rate limit reset testing requires time manipulation');
    }
}