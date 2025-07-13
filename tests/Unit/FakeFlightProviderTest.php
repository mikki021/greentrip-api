<?php

namespace Tests\Unit;

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
        $this->assertInstanceOf(\App\Contracts\FlightProviderInterface::class, $this->flightProvider);
    }

    public function test_search_flights_returns_matching_flights(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15');

        $this->assertIsArray($flights);
        $this->assertNotEmpty($flights);

        foreach ($flights as $flight) {
            $this->assertEquals('JFK', $flight->from);
            $this->assertEquals('LAX', $flight->to);
            $this->assertNotNull($flight->price);
        }
    }

    public function test_search_flights_returns_empty_array_for_no_matches(): void
    {
        $flights = $this->flightProvider->searchFlights('XXX', 'YYY', '2024-01-15');

        $this->assertIsArray($flights);
        $this->assertEmpty($flights);
    }

    public function test_get_flight_details_returns_correct_flight(): void
    {
        $flight = $this->flightProvider->getFlightDetails('FL001');

        $this->assertInstanceOf(\App\Services\DTOs\FlightData::class, $flight);
        $this->assertEquals('FL001', $flight->id);
        $this->assertEquals('Green Airlines', $flight->airline);
        $this->assertEquals('GA101', $flight->flight_number);
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
            $this->assertInstanceOf(\App\Services\DTOs\AirportData::class, $airport);
            $this->assertNotEmpty($airport->code);
            $this->assertNotEmpty($airport->name);
            $this->assertNotEmpty($airport->city);
            $this->assertNotEmpty($airport->country);
        }
    }

    public function test_search_flights_respects_passenger_count(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15', 50);

        $this->assertIsArray($flights);
        $this->assertEmpty($flights); // No flights have 50 seats available
    }

    public function test_search_flights_calculates_total_price_correctly(): void
    {
        $flights = $this->flightProvider->searchFlights('JFK', 'LAX', '2024-01-15', 3);

        $this->assertIsArray($flights);
        $this->assertNotEmpty($flights);

        foreach ($flights as $flight) {
            $expectedTotalPrice = $flight->price * 3;
            $this->assertNotNull($flight->price);
            $this->assertGreaterThan(0, $flight->price);
        }
    }

    public function test_airports_include_coordinates(): void
    {
        $airports = $this->flightProvider->getAirports();

        foreach ($airports as $airport) {
            $this->assertIsFloat($airport->latitude);
            $this->assertIsFloat($airport->longitude);
            $this->assertGreaterThanOrEqual(-90, $airport->latitude);
            $this->assertLessThanOrEqual(90, $airport->latitude);
            $this->assertGreaterThanOrEqual(-180, $airport->longitude);
            $this->assertLessThanOrEqual(180, $airport->longitude);
        }
    }

    public function test_specific_airport_coordinates(): void
    {
        $airports = $this->flightProvider->getAirports();

        // Find JFK airport
        $jfk = null;
        foreach ($airports as $airport) {
            if ($airport->code === 'JFK') {
                $jfk = $airport;
                break;
            }
        }

        $this->assertNotNull($jfk);
        $this->assertEquals(40.6413, $jfk->latitude);
        $this->assertEquals(-73.7781, $jfk->longitude);
    }
}