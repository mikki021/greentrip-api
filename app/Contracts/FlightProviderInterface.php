<?php

namespace App\Contracts;

interface FlightProviderInterface
{
    /**
     * Search for flights based on criteria
     *
     * @param string $from Origin airport code
     * @param string $to Destination airport code
     * @param string $date Departure date (Y-m-d format)
     * @param int $passengers Number of passengers
     * @return array Array of flight options
     */
    public function searchFlights(string $from, string $to, string $date, int $passengers = 1): array;

    /**
     * Get flight details by ID
     *
     * @param string $flightId Unique flight identifier
     * @return array|null Flight details or null if not found
     */
    public function getFlightDetails(string $flightId): ?array;

    /**
     * Get available airports
     *
     * @return array Array of available airports
     */
    public function getAirports(): array;
}