<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmissionCalculatorService;
use App\Services\FlightService;
use Tests\TestCase;

class EmissionControllerTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        $this->token = $response->json('authorization.token');
    }

    public function test_calculate_emissions_with_valid_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'LHR',
            'to' => 'JFK',
            'class' => 'economy',
            'passengers' => 2
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'from',
                'to',
                'class',
                'passengers',
                'distance_km',
                'emissions_kg'
            ]);

        $data = $response->json();
        $this->assertEquals('LHR', $data['from']);
        $this->assertEquals('JFK', $data['to']);
        $this->assertEquals('economy', $data['class']);
        $this->assertEquals(2, $data['passengers']);
        $this->assertIsNumeric($data['distance_km']);
        $this->assertIsNumeric($data['emissions_kg']);
        $this->assertGreaterThan(0, $data['distance_km']);
        $this->assertGreaterThan(0, $data['emissions_kg']);
    }

    public function test_calculate_emissions_with_business_class(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'CDG',
            'to' => 'LAX',
            'class' => 'business',
            'passengers' => 1
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('business', $data['class']);
        $this->assertEquals(1, $data['passengers']);
    }

    public function test_calculate_emissions_with_first_class(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'SIN',
            'to' => 'HND',
            'class' => 'first',
            'passengers' => 3
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('first', $data['class']);
        $this->assertEquals(3, $data['passengers']);
    }

    public function test_calculate_emissions_with_unknown_airport(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'XXX',
            'to' => 'JFK',
            'class' => 'economy',
            'passengers' => 1
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Unknown IATA code(s).',
                'from' => 'XXX',
                'to' => 'JFK'
            ]);
    }

    public function test_calculate_emissions_with_same_airports(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'JFK',
            'to' => 'JFK',
            'class' => 'economy',
            'passengers' => 1
        ]);

        $response->assertStatus(422);
    }

    public function test_calculate_emissions_with_invalid_class(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'LHR',
            'to' => 'JFK',
            'class' => 'invalid_class',
            'passengers' => 1
        ]);

        $response->assertStatus(422);
    }

    public function test_calculate_emissions_with_invalid_passengers(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/emissions/calculate', [
            'from' => 'LHR',
            'to' => 'JFK',
            'class' => 'economy',
            'passengers' => 0
        ]);

        $response->assertStatus(422);
    }
}