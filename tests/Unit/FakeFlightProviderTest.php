<?php

namespace Tests\Unit;

use App\Contracts\FlightProviderInterface;
use App\Services\FakeFlightProvider;
use Tests\TestCase;

class FakeFlightProviderTest extends TestCase
{
    private FakeFlightProvider $flightProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flightProvider = new FakeFlightProvider();
    }

    public function test_implements_flight_provider_interface(): void
    {
        $this->assertInstanceOf(FlightProviderInterface::class, $this->flightProvider);
    }

    public function test_search_flights_returns_matching_flights(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15', 1);

        $this->assertIsArray($flights);
        $this->assertNotEmpty($flights);

        foreach ($flights as $flight) {
            $this->assertEquals('JFK', $flight['from']);
            $this->assertEquals('LAX', $flight['to']);
            $this->assertEquals('2024-01-15', $flight['date']);
            $this->assertArrayHasKey('total_price', $flight);
        }
    }

    public function test_search_flights_returns_empty_array_for_no_matches(): void
    {
        $flights = $this->flightProvider->searchFlights('INVALID', 'INVALID', '2024-01-15', 1);

        $this->assertIsArray($flights);
        $this->assertEmpty($flights);
    }

    public function test_get_flight_details_returns_correct_flight(): void
    {
        $flight = $this->flightProvider->getFlightDetails('FL001');

        $this->assertIsArray($flight);
        $this->assertEquals('FL001', $flight['id']);
        $this->assertEquals('Green Airlines', $flight['airline']);
        $this->assertEquals('GA101', $flight['flight_number']);
    }

    public function test_get_flight_details_returns_null_for_invalid_id(): void
    {
        $flight = $this->flightProvider->getFlightDetails('INVALID');

        $this->assertNull($flight);
    }

    public function test_get_airports_returns_airports_array(): void
    {
        $airports = $this->flightProvider->getAirports();

        $this->assertIsArray($airports);
        $this->assertNotEmpty($airports);

        foreach ($airports as $airport) {
            $this->assertArrayHasKey('code', $airport);
            $this->assertArrayHasKey('name', $airport);
            $this->assertArrayHasKey('city', $airport);
            $this->assertArrayHasKey('country', $airport);
        }
    }

    public function test_search_flights_respects_passenger_count(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15', 100);

        $this->assertIsArray($flights);
        $this->assertEmpty($flights);
    }

    public function test_search_flights_calculates_total_price_correctly(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15', 3);

        $this->assertIsArray($flights);
        $this->assertNotEmpty($flights);

        foreach ($flights as $flight) {
            $expectedTotalPrice = $flight['price'] * 3;
            $this->assertEquals($expectedTotalPrice, $flight['total_price']);
        }
    }
}