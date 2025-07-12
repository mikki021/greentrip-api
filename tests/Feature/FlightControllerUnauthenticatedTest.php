<?php

namespace Tests\Feature;

use Tests\TestCase;

class FlightControllerUnauthenticatedTest extends TestCase
{
    public function test_unauthenticated_access_to_search_flights_returns_401(): void
    {
        $response = $this->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d'),
            'passengers' => 2
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_access_to_book_flight_returns_401(): void
    {
        $response = $this->postJson('/api/flights/book', [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'AB123456'
                ]
            ],
            'contact_email' => 'john.doe@example.com'
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_access_to_flight_details_returns_401(): void
    {
        $response = $this->getJson('/api/flights/FL001');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_access_to_airports_returns_401(): void
    {
        $response = $this->getJson('/api/flights/airports');

        $response->assertStatus(401);
    }
}