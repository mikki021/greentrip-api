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

    public function test_user_can_create_booking_via_api(): void
    {
        $this->actingAs($this->user, 'api');

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

        $response = $this->postJson('/api/bookings', $bookingData);

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
                    'booking_date',
                    'status',
                    'carbon_offset_contribution'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Booking created successfully', $response->json('message'));
    }

    public function test_user_can_update_booking_status(): void
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
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'modified'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking updated successfully'
            ]);

        $this->assertEquals('modified', $response->json('data.status'));
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
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        // Verify booking is soft deleted
        $this->assertSoftDeleted($booking);

        // Verify status was updated to cancelled before deletion
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_user_cannot_update_other_user_booking(): void
    {
        // Create a booking for other user
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

        $this->flightService->bookFlight($bookingData, $this->otherUser);
        $otherUserBooking = Booking::where('user_id', $this->otherUser->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->putJson("/api/bookings/{$otherUserBooking->id}", [
            'status' => 'modified'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }

    public function test_user_cannot_cancel_other_user_booking(): void
    {
        // Create a booking for other user
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

        $this->flightService->bookFlight($bookingData, $this->otherUser);
        $otherUserBooking = Booking::where('user_id', $this->otherUser->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->deleteJson("/api/bookings/{$otherUserBooking->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }

    public function test_update_booking_validates_status_values(): void
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
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_update_booking_validates_emissions_value(): void
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
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
        $booking = Booking::where('user_id', $this->user->id)->first();

        $this->actingAs($this->user, 'api');

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'emissions' => -10
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
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
            'contact_email' => 'john@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
        $booking = Booking::where('user_id', $this->user->id)->first();

        // Cancel the booking
        $this->actingAs($this->user, 'api');
        $this->deleteJson("/api/bookings/{$booking->id}");

        // Check that cancelled booking is not in the list
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'count' => 0
            ]);
    }

    public function test_booking_policy_prevents_unauthorized_access(): void
    {
        // Create a booking for other user
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

        $this->flightService->bookFlight($bookingData, $this->otherUser);
        $otherUserBooking = Booking::where('user_id', $this->otherUser->id)->first();

        $this->actingAs($this->user, 'api');

        // Try to view other user's booking
        $response = $this->getJson("/api/bookings/{$otherUserBooking->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found'
            ]);
    }
}
