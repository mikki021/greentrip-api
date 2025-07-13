<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\User;
use App\Services\EmissionCalculatorService;
use App\Services\FakeFlightProvider;
use App\Services\FlightService;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    private FlightService $flightService;
    private User $user;
    private User $otherUser;

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
        $this->otherUser = User::factory()->create();
    }

    public function test_user_can_cancel_booking(): void
    {
        // Create a booking first
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking);

        // Cancel the booking
        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        // Verify booking is soft deleted
        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);

        // Verify status was updated to cancelled
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_user_cannot_cancel_other_user_booking(): void
    {
        // Create a booking for the first user
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();

        // Try to cancel with different user
        $this->actingAs($this->otherUser, 'api');
        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);

        // Verify booking is not cancelled
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed'
        ]);
    }

    public function test_cancelled_bookings_are_not_returned_in_list(): void
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();

        // Cancel the booking
        $this->deleteJson("/api/bookings/{$booking->id}");

        // Get bookings list
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200);
        $bookings = $response->json('data');
        $this->assertCount(0, $bookings); // No bookings should be returned
    }

    public function test_booking_policy_prevents_unauthorized_access(): void
    {
        // Create a booking for the first user
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();

        // Try to access with different user
        $this->actingAs($this->otherUser, 'api');
        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }
}
