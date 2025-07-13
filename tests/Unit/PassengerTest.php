<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use App\Services\DTOs\PassengerData;
use App\Services\EmissionCalculatorService;
use App\Services\FakeFlightProvider;
use App\Services\FlightService;
use Tests\TestCase;

class PassengerTest extends TestCase
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

    public function test_passenger_data_dto_creation(): void
    {
        $passengerData = new PassengerData(
            first_name: 'John',
            last_name: 'Doe',
            date_of_birth: '1990-01-01',
            passport_number: 'ABC123456'
        );

        $this->assertEquals('John', $passengerData->first_name);
        $this->assertEquals('Doe', $passengerData->last_name);
        $this->assertEquals('1990-01-01', $passengerData->date_of_birth);
        $this->assertEquals('ABC123456', $passengerData->passport_number);
    }

    public function test_passenger_data_dto_from_array(): void
    {
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => '1985-05-15',
            'passport_number' => 'XYZ789012'
        ];

        $passengerData = PassengerData::fromArray($data);

        $this->assertEquals('Jane', $passengerData->first_name);
        $this->assertEquals('Smith', $passengerData->last_name);
        $this->assertEquals('1985-05-15', $passengerData->date_of_birth);
        $this->assertEquals('XYZ789012', $passengerData->passport_number);
    }

    public function test_passenger_data_dto_to_array(): void
    {
        $passengerData = new PassengerData(
            first_name: 'Alice',
            last_name: 'Johnson',
            date_of_birth: '1992-12-25',
            passport_number: 'DEF456789'
        );

        $array = $passengerData->toArray();

        $this->assertEquals([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'date_of_birth' => '1992-12-25',
            'passport_number' => 'DEF456789'
        ], $array);
    }

    public function test_passenger_data_creates_model(): void
    {
        // Create a booking first
        $bookingData = [
            'flight_id' => 'FL001',
            'passengers' => 1,
            'class' => 'economy',
            'passenger_details' => [
                [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '1990-01-01',
                    'passport_number' => 'TEST123456'
                ]
            ],
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking);

        $passengerData = new PassengerData(
            first_name: 'John',
            last_name: 'Doe',
            date_of_birth: '1990-01-01',
            passport_number: 'ABC123456'
        );

        $passenger = $passengerData->createModel($booking->id);

        $this->assertInstanceOf(Passenger::class, $passenger);
        $this->assertEquals($booking->id, $passenger->booking_id);
        $this->assertEquals('John', $passenger->first_name);
        $this->assertEquals('Doe', $passenger->last_name);
        $this->assertEquals('1990-01-01', $passenger->date_of_birth->format('Y-m-d'));
        $this->assertEquals('ABC123456', $passenger->passport_number);
    }

    public function test_booking_creates_passengers_via_dto(): void
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
                    'last_name' => 'Smith',
                    'date_of_birth' => '1992-05-15',
                    'passport_number' => 'XYZ789012'
                ]
            ],
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $response = $this->postJson('/api/flights/book', $bookingData);

        $response->assertStatus(201);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking);

        $passengers = $booking->passengers;
        $this->assertCount(2, $passengers);

        $firstPassenger = $passengers->first();
        $this->assertEquals('John', $firstPassenger->first_name);
        $this->assertEquals('Doe', $firstPassenger->last_name);
        $this->assertEquals('1990-01-01', $firstPassenger->date_of_birth->format('Y-m-d'));
        $this->assertEquals('ABC123456', $firstPassenger->passport_number);

        $secondPassenger = $passengers->last();
        $this->assertEquals('Jane', $secondPassenger->first_name);
        $this->assertEquals('Smith', $secondPassenger->last_name);
        $this->assertEquals('1992-05-15', $secondPassenger->date_of_birth->format('Y-m-d'));
        $this->assertEquals('XYZ789012', $secondPassenger->passport_number);
    }

    public function test_passenger_booking_relationship(): void
    {
        // Create a booking with passengers
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
        $passenger = $booking->passengers->first();

        $this->assertEquals($booking->id, $passenger->booking->id);
        $this->assertEquals($this->user->id, $passenger->booking->user_id);
    }

    public function test_passengers_included_in_booking_api_response(): void
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
                    'last_name' => 'Smith',
                    'date_of_birth' => '1992-05-15',
                    'passport_number' => 'XYZ789012'
                ]
            ],
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'booking_reference',
                    'flight_details',
                    'passengers' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'date_of_birth',
                            'passport_number'
                        ]
                    ],
                    'emissions',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $passengers = $response->json('data.passengers');
        $this->assertCount(2, $passengers);
        $this->assertEquals('John', $passengers[0]['first_name']);
        $this->assertEquals('Jane', $passengers[1]['first_name']);
    }

    public function test_passengers_included_in_bookings_list(): void
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'booking_reference',
                        'flight_details',
                        'passengers' => [
                            '*' => [
                                'id',
                                'first_name',
                                'last_name',
                                'date_of_birth',
                                'passport_number'
                            ]
                        ],
                        'emissions',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'count'
            ]);

        $bookings = $response->json('data');
        $this->assertCount(1, $bookings);
        $this->assertCount(1, $bookings[0]['passengers']);
        $this->assertEquals('John', $bookings[0]['passengers'][0]['first_name']);
    }

    public function test_passengers_cascade_delete_with_booking(): void
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
            'contact_email' => 'test@example.com'
        ];

        $this->actingAs($this->user, 'api');
        $this->postJson('/api/flights/book', $bookingData);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $passenger = $booking->passengers->first();

        $this->assertNotNull($passenger);

        // Cancel the booking (soft delete)
        $this->deleteJson("/api/bookings/{$booking->id}");

        // Passenger should still exist since booking is soft deleted
        $this->assertDatabaseHas('passengers', ['id' => $passenger->id]);

        // If we were to hard delete the booking, passengers would be deleted due to cascade
        // But we're using soft deletes, so passengers remain
    }
}
