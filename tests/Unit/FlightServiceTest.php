<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\EmissionCalculatorService;
use App\Services\FlightService;
use App\Services\FakeFlightProvider;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FlightServiceTest extends TestCase
{
    private FlightService $flightService;
    private FakeFlightProvider $mockProvider;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProvider = new FakeFlightProvider();
        $this->flightService = new FlightService(
            $this->mockProvider,
            new EmissionCalculatorService()
        );
        $this->user = User::factory()->create();
    }

    public function test_search_flights_with_valid_criteria(): void
    {
        $searchCriteria = [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => now()->addDays(30)->format('Y-m-d'),
            'passengers' => 2
        ];

        $result = $this->flightService->searchFlights($searchCriteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('flights', $result);
        $this->assertArrayHasKey('search_criteria', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('search_timestamp', $result);
        $this->assertEquals($searchCriteria, $result['search_criteria']);
        $this->assertGreaterThan(0, $result['total_count']);
    }

    public function test_search_flights_throws_validation_exception_for_invalid_airport_code(): void
    {
        $this->expectException(ValidationException::class);

        $searchCriteria = [
            'from' => 'INVALID',
            'to' => 'LAX',
            'date' => '2024-12-25'
        ];

        $this->flightService->searchFlights($searchCriteria);
    }

    public function test_search_flights_throws_validation_exception_for_past_date(): void
    {
        $this->expectException(ValidationException::class);

        $searchCriteria = [
            'from' => 'JFK',
            'to' => 'LAX',
            'date' => '2020-01-01'
        ];

        $this->flightService->searchFlights($searchCriteria);
    }

    public function test_search_flights_throws_validation_exception_for_same_airports(): void
    {
        $this->expectException(ValidationException::class);

        $searchCriteria = [
            'from' => 'JFK',
            'to' => 'JFK',
            'date' => '2024-12-25'
        ];

        $this->flightService->searchFlights($searchCriteria);
    }

    public function test_book_flight_with_valid_data(): void
    {
        $bookingData = [
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
        ];

        $booking = $this->flightService->bookFlight($bookingData, $this->user);

        $this->assertIsArray($booking);
        $this->assertArrayHasKey('booking_reference', $booking);
        $this->assertArrayHasKey('flight_id', $booking);
        $this->assertArrayHasKey('flight_details', $booking);
        $this->assertArrayHasKey('passengers', $booking);
        $this->assertArrayHasKey('total_price', $booking);
        $this->assertArrayHasKey('passenger_details', $booking);
        $this->assertArrayHasKey('contact_email', $booking);
        $this->assertArrayHasKey('booking_date', $booking);
        $this->assertArrayHasKey('status', $booking);
        $this->assertArrayHasKey('carbon_offset_contribution', $booking);

        $this->assertEquals('FL001', $booking['flight_id']);
        $this->assertEquals(2, $booking['passengers']);
        $this->assertEquals('confirmed', $booking['status']);
        $this->assertStringStartsWith('GT', $booking['booking_reference']);
    }

    public function test_book_flight_throws_validation_exception_for_invalid_flight_id(): void
    {
        $this->expectException(ValidationException::class);

        $bookingData = [
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
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
    }

    public function test_book_flight_throws_validation_exception_for_insufficient_seats(): void
    {
        $this->expectException(ValidationException::class);

        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 100, // More than available seats
            'passenger_details' => array_fill(0, 100, [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'passport_number' => 'AB123456'
            ]),
            'contact_email' => 'john.doe@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
    }

    public function test_book_flight_throws_validation_exception_for_missing_passenger_details(): void
    {
        $this->expectException(ValidationException::class);

        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 2,
            'passenger_details' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'AB123456'
                ]
                // Missing second passenger details
            ],
            'contact_email' => 'john.doe@example.com'
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
    }

    public function test_book_flight_throws_validation_exception_for_invalid_email(): void
    {
        $this->expectException(ValidationException::class);

        $bookingData = [
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
        ];

        $this->flightService->bookFlight($bookingData, $this->user);
    }

    public function test_get_airports_returns_airports_array(): void
    {
        $airports = $this->flightService->getAirports();

        $this->assertIsArray($airports);
        $this->assertNotEmpty($airports);

        foreach ($airports as $airport) {
            $this->assertArrayHasKey('code', $airport);
            $this->assertArrayHasKey('name', $airport);
            $this->assertArrayHasKey('city', $airport);
            $this->assertArrayHasKey('country', $airport);
        }
    }

    public function test_get_flight_details_returns_correct_flight(): void
    {
        $flight = $this->flightService->getFlightDetails('FL001');

        $this->assertIsArray($flight);
        $this->assertEquals('FL001', $flight['id']);
        $this->assertEquals('Green Airlines', $flight['airline']);
        $this->assertEquals('GA101', $flight['flight_number']);
    }

    public function test_get_flight_details_returns_null_for_invalid_id(): void
    {
        $flight = $this->flightService->getFlightDetails('INVALID');

        $this->assertNull($flight);
    }

    public function test_booking_reference_is_unique(): void
    {
        $bookingData = [
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
        ];

        $booking1 = $this->flightService->bookFlight($bookingData, $this->user);
        $booking2 = $this->flightService->bookFlight($bookingData, $this->user);

        $this->assertNotEquals($booking1['booking_reference'], $booking2['booking_reference']);
        $this->assertStringStartsWith('GT', $booking1['booking_reference']);
        $this->assertStringStartsWith('GT', $booking2['booking_reference']);
    }

    public function test_carbon_offset_calculation(): void
    {
        $bookingData = [
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
            'contact_email' => 'john.doe@example.com'
        ];

        $booking = $this->flightService->bookFlight($bookingData, $this->user);

        $this->assertArrayHasKey('carbon_offset_contribution', $booking);
        $this->assertGreaterThan(0, $booking['carbon_offset_contribution']);
    }
}