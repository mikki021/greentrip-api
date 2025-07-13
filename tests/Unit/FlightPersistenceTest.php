<?php

namespace Tests\Unit;

use App\DataTransferObjects\FlightData;
use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\User;
use App\Services\EmissionCalculatorService;
use App\Services\FakeFlightProvider;
use App\Services\FlightService;
use Tests\TestCase;

class FlightPersistenceTest extends TestCase
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

    public function test_booking_creates_flight_detail_and_booking_records(): void
    {
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 2,
            'class' => 'economy',
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
            'contact_email' => 'john@example.com',
            'contact_phone' => '+1234567890'
        ];

        $result = $this->flightService->bookFlight($bookingData, $this->user);

        // Verify booking was created
        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'status' => 'confirmed'
        ]);

        // Verify flight detail was created
        $this->assertDatabaseHas('flight_details', [
            'flight_id' => 'FL001',
            'airline' => 'Green Airlines',
            'flight_number' => 'GA101'
        ]);

        // Verify booking has emissions
        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking->emissions);
        $this->assertGreaterThan(0, $booking->emissions);

        // Verify relationships
        $this->assertNotNull($booking->flightDetail);
        $this->assertEquals('FL001', $booking->flightDetail->flight_id);
    }

    public function test_flight_detail_deduplication_works(): void
    {
        $bookingData1 = [
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

        $bookingData2 = [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'class' => 'business',
            'passenger_details' => [
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'date_of_birth' => '1992-01-01',
                    'passport_number' => 'DEF789012'
                ]
            ],
            'contact_email' => 'jane@example.com'
        ];

        // Create two bookings for the same flight
        $this->flightService->bookFlight($bookingData1, $this->user);
        $this->flightService->bookFlight($bookingData2, $this->user);

        // Should only have one flight detail record
        $this->assertEquals(1, FlightDetail::count());

        // Should have two booking records
        $this->assertEquals(2, Booking::count());

        // Both bookings should reference the same flight detail
        $bookings = Booking::all();
        $this->assertEquals($bookings[0]->flight_details_id, $bookings[1]->flight_details_id);
    }

    public function test_emissions_are_calculated_and_stored(): void
    {
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 3,
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
                ],
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1995-01-01',
                    'passport_number' => 'GHI345678'
                ]
            ],
            'contact_email' => 'john@example.com'
        ];

        $result = $this->flightService->bookFlight($bookingData, $this->user);

        $booking = Booking::where('user_id', $this->user->id)->first();

        // Verify emissions are calculated and stored
        $this->assertNotNull($booking->emissions);
        $this->assertGreaterThan(0, $booking->emissions);

        // Verify emissions match the carbon_offset_contribution in the response
        $this->assertEquals($booking->emissions, $result['carbon_offset_contribution']);
    }

    public function test_flight_detail_from_flight_data_method(): void
    {
        $flightData = new FlightData(
            id: 'FL001',
            airline: 'Green Airlines',
            flight_number: 'GA101',
            from: 'JFK',
            to: 'LAX',
            departure_time: '10:00',
            arrival_time: '13:30',
            duration: '5h 30m',
            price: 299.99,
            seats_available: 45,
            aircraft: 'Boeing 737',
            carbon_footprint: 0.85,
            eco_rating: 4.2,
            date: '2024-01-15',
            total_price: 599.98
        );

        $flightDetail = FlightDetail::fromFlightData($flightData);

        $this->assertDatabaseHas('flight_details', [
            'flight_id' => 'FL001',
            'airline' => 'Green Airlines',
            'flight_number' => 'GA101',
            'date' => '2024-01-15'
        ]);

        // Test deduplication
        $flightDetail2 = FlightDetail::fromFlightData($flightData);
        $this->assertEquals($flightDetail->id, $flightDetail2->id);
        $this->assertEquals(1, FlightDetail::count());
    }

    public function test_user_booking_relationship(): void
    {
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

        // Test user relationship
        $this->assertEquals(1, $this->user->bookings()->count());
        $booking = $this->user->bookings()->first();
        $this->assertEquals($this->user->id, $booking->user_id);
    }

    public function test_flight_detail_booking_relationship(): void
    {
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

        $flightDetail = FlightDetail::where('flight_id', 'FL001')->first();

        // Test flight detail relationship
        $this->assertEquals(1, $flightDetail->bookings()->count());
        $booking = $flightDetail->bookings()->first();
        $this->assertEquals($flightDetail->id, $booking->flight_details_id);
    }
}
