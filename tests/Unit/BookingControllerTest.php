<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\User;
use App\Services\EmissionCalculatorService;
use App\Services\FakeFlightProvider;
use App\Services\FlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    private FlightService $flightService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear the database before each test
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);

        $this->flightService = new FlightService(
            new FakeFlightProvider(),
            new EmissionCalculatorService()
        );

        $this->user = User::factory()->create();
    }

    public function test_user_bookings_endpoint_returns_user_bookings(): void
    {
        // Create a booking for the user
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'class' => 'economy',
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'ABC123456'
                ]
            ],
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);

        // Authenticate and call the endpoint
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'booking_reference',
                        'flight_details',
                        'emissions',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'count'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(1, $response->json('count'));
    }

    public function test_user_bookings_endpoint_returns_empty_for_no_bookings(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'count' => 0
            ]);
    }

    public function test_show_booking_endpoint_returns_booking_details(): void
    {
        // Create a booking for the user
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 2,
            'class' => 'business',
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'ABC123456'
                ],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1992-01-01',
                    'passport_number' => 'DEF789012'
                ]
            ],
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);

        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'booking_reference',
                    'flight_details',
                    'emissions',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($booking->id, $response->json('data.id'));
        $this->assertNotNull($response->json('data.emissions'));
        $this->assertEquals('confirmed', $response->json('data.status'));
    }

    public function test_show_booking_endpoint_returns_404_for_nonexistent_booking(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/bookings/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }

    public function test_show_booking_endpoint_returns_404_for_other_user_booking(): void
    {
        // Create another user and booking
        $otherUser = User::factory()->create();

        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'class' => 'economy',
            'passenger_details' => [
                [
                    'first_name' => 'Other',
                    'last_name' => 'User',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'XYZ123456'
                ]
            ],
            'contact_email' => 'other@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $otherUser);

        $otherUserBooking = Booking::where('user_id', $otherUser->id)->first();

        // Try to access other user's booking
        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/bookings/{$otherUserBooking->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }

    public function test_booking_endpoints_require_authentication(): void
    {
        // Test without authentication
        $response = $this->getJson('/api/bookings');
        $response->assertStatus(401);

        $response = $this->getJson('/api/bookings/1');
        $response->assertStatus(401);
    }

    public function test_booking_reference_is_generated_correctly(): void
    {
        // Create a booking
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'class' => 'economy',
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'ABC123456'
                ]
            ],
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);

        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $bookingReference = $response->json('data.booking_reference');

        $this->assertStringStartsWith('GT', $bookingReference);
        $this->assertEquals(10, strlen($bookingReference)); // GT + 8 characters
    }
}
