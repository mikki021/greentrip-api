<?php

namespace Tests\Unit;

use App\Contracts\FlightProviderInterface;
use App\Services\FakeFlightProvider;
use Tests\TestCase;

class FlightProviderBindingTest extends TestCase
{
    public function test_flight_provider_interface_is_bound_to_fake_provider(): void
    {
        $flightProvider = app(FlightProviderInterface::class);

        $this->assertInstanceOf(FakeFlightProvider::class, $flightProvider);
        $this->assertInstanceOf(FlightProviderInterface::class, $flightProvider);
    }

    public function test_can_resolve_flight_provider_from_container(): void
    {
        $flightProvider = $this->app->make(FlightProviderInterface::class);

        $this->assertInstanceOf(FakeFlightProvider::class, $flightProvider);
    }

    public function test_flight_provider_methods_work_through_interface(): void
    {
        $flightProvider = app(FlightProviderInterface::class);

        $flights = $flightProvider->searchFlights('JFK', 'LAX', '2024-01-15');
        $this->assertIsArray($flights);

        $flight = $flightProvider->getFlightDetails('FL001');
        $this->assertIsArray($flight);

        $airports = $flightProvider->getAirports();
        $this->assertIsArray($airports);
    }
}