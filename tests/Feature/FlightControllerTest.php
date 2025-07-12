<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class FlightControllerTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->token = $response->json('authorization.token');
    }

    public function test_search_flights_endpoint_with_valid_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d'),
            'passengers' => 2
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'flights',
                    'search_criteria',
                    'total_count',
                    'search_timestamp'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('flights', $data);
        $this->assertArrayHasKey('search_criteria', $data);
        $this->assertArrayHasKey('total_count', $data);
        $this->assertArrayHasKey('search_timestamp', $data);
    }

    public function test_search_flights_endpoint_with_invalid_airport_code(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'INVALID',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d')
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_search_flights_endpoint_with_past_date(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => '2020-01-01'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_search_flights_endpoint_with_same_airports(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/search', [
            'from' => 'JFK',
            'to' => 'JFK',
            'date' => now()->addDays(30)->format('Y-m-d')
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_book_flight_endpoint_with_valid_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/book', [
            'flight_id' => 'FL001',
            'passengers' => 2,
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'AB123456'
                ],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1992-05-15',
                    'passport_number' => 'CD789012'
                ]
            ],
            'contact_email' => 'john.doe@example.com',
            'contact_phone' => '+1234567890'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'booking_reference',
                    'flight_id',
                    'flight_details',
                    'passengers',
                    'total_price',
                    'passenger_details',
                    'contact_email',
                    'contact_phone',
                    'booking_date',
                    'status',
                    'carbon_offset_contribution'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Flight booked successfully'
            ]);

        $data = $response->json('data');
        $this->assertEquals('FL001', $data['flight_id']);
        $this->assertEquals(2, $data['passengers']);
        $this->assertEquals('confirmed', $data['status']);
        $this->assertStringStartsWith('GT', $data['booking_reference']);
    }

    public function test_book_flight_endpoint_with_invalid_flight_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/book', [
            'flight_id' => 'INVALID',
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

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_book_flight_endpoint_with_insufficient_seats(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/book', [
            'flight_id' => 'FL001',
            'passengers' => 100,
            'passenger_details' => array_fill(0, 100, [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'passport_number' => 'AB123456'
            ]),
            'contact_email' => 'john.doe@example.com'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_book_flight_endpoint_with_invalid_email(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/flights/book', [
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
            'contact_email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_get_flight_details_endpoint_with_valid_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/flights/FL001');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'airline',
                    'flight_number',
                    'from',
                    'to',
                    'departure_time',
                    'arrival_time',
                    'duration',
                    'price',
                    'seats_available',
                    'aircraft',
                    'carbon_footprint',
                    'eco_rating'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 'FL001',
                    'airline' => 'Green Airlines'
                ]
            ]);
    }

    public function test_get_flight_details_endpoint_with_invalid_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/flights/INVALID');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Flight not found'
            ]);
    }

    public function test_get_airports_endpoint(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/flights/airports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'city',
                        'country'
                    ]
                ],
                'count'
            ])
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        $this->assertEquals(count($data), $response->json('count'));
    }
}