<?php

namespace Tests\Unit;

use App\Contracts\FlightProviderInterface;
use Tests\TestCase;

class FlightProviderBindingTest extends TestCase
{
    public function test_flight_provider_interface_is_bound_to_fake_provider(): void
    {
        $flightProvider = app(FlightProviderInterface::class);

        $this->assertInstanceOf(\App\Services\FakeFlightProvider::class, $flightProvider);
    }

    public function test_can_resolve_flight_provider_from_container(): void
    {
        $flightProvider = app(FlightProviderInterface::class);

        $this->assertInstanceOf(FlightProviderInterface::class, $flightProvider);
    }

    public function test_flight_provider_methods_work_through_interface(): void
    {
        $flightProvider = app(FlightProviderInterface::class);

        $flights = $flightProvider->searchFlights('JFK', 'LAX', '2024-01-15');
        $this->assertIsArray($flights);

        $flight = $flightProvider->getFlightDetails('FL001');
        $this->assertInstanceOf(\App\Services\DTOs\FlightData::class, $flight);

        $airports = $flightProvider->getAirports();
        $this->assertIsArray($airports);
        $this->assertInstanceOf(\App\Services\DTOs\AirportData::class, $airports[0]);
    }
}