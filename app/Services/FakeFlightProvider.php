<?php

namespace App\Services;

use App\Contracts\FlightProviderInterface;

class FakeFlightProvider implements FlightProviderInterface
{
    /**
     * Static flight data for testing
     */
    private array $flights = [
        'FL001' => [
            'id' => 'FL001',
            'airline' => 'Green Airlines',
            'flight_number' => 'GA101',
            'from' => 'JFK',
            'to' => 'LAX',
            'departure_time' => '10:00',
            'arrival_time' => '13:30',
            'duration' => '5h 30m',
            'price' => 299.99,
            'seats_available' => 45,
            'aircraft' => 'Boeing 737',
            'carbon_footprint' => 0.85,
            'eco_rating' => 4.2
        ],
        'FL002' => [
            'id' => 'FL002',
            'airline' => 'EcoJet',
            'flight_number' => 'EJ202',
            'from' => 'LAX',
            'to' => 'JFK',
            'departure_time' => '14:15',
            'arrival_time' => '22:45',
            'duration' => '6h 30m',
            'price' => 349.99,
            'seats_available' => 32,
            'aircraft' => 'Airbus A320neo',
            'carbon_footprint' => 0.72,
            'eco_rating' => 4.5
        ],
        'FL003' => [
            'id' => 'FL003',
            'airline' => 'Sustainable Airways',
            'flight_number' => 'SA303',
            'from' => 'ORD',
            'to' => 'SFO',
            'departure_time' => '08:30',
            'arrival_time' => '11:45',
            'duration' => '4h 15m',
            'price' => 199.99,
            'seats_available' => 67,
            'aircraft' => 'Boeing 787 Dreamliner',
            'carbon_footprint' => 0.68,
            'eco_rating' => 4.8
        ],
        'FL004' => [
            'id' => 'FL004',
            'airline' => 'Green Airlines',
            'flight_number' => 'GA404',
            'from' => 'SFO',
            'to' => 'ORD',
            'departure_time' => '16:00',
            'arrival_time' => '22:15',
            'duration' => '4h 15m',
            'price' => 249.99,
            'seats_available' => 28,
            'aircraft' => 'Airbus A350',
            'carbon_footprint' => 0.71,
            'eco_rating' => 4.6
        ]
    ];

    /**
     * Static airports data
     */
    private array $airports = [
        'JFK' => [
            'code' => 'JFK',
            'name' => 'John F. Kennedy International Airport',
            'city' => 'New York',
            'country' => 'USA'
        ],
        'LAX' => [
            'code' => 'LAX',
            'name' => 'Los Angeles International Airport',
            'city' => 'Los Angeles',
            'country' => 'USA'
        ],
        'ORD' => [
            'code' => 'ORD',
            'name' => 'O\'Hare International Airport',
            'city' => 'Chicago',
            'country' => 'USA'
        ],
        'SFO' => [
            'code' => 'SFO',
            'name' => 'San Francisco International Airport',
            'city' => 'San Francisco',
            'country' => 'USA'
        ],
        'MIA' => [
            'code' => 'MIA',
            'name' => 'Miami International Airport',
            'city' => 'Miami',
            'country' => 'USA'
        ],
        'SEA' => [
            'code' => 'SEA',
            'name' => 'Seattle-Tacoma International Airport',
            'city' => 'Seattle',
            'country' => 'USA'
        ]
    ];

    /**
     * Search for flights based on criteria
     */
    public function searchFlights(string $from, string $to, string $date, int $passengers = 1): array
    {
        $availableFlights = [];

        foreach ($this->flights as $flight) {
            if ($flight['from'] === strtoupper($from) &&
                $flight['to'] === strtoupper($to) &&
                $flight['seats_available'] >= $passengers) {

                $flightData = $flight;
                $flightData['date'] = $date;
                $flightData['total_price'] = $flight['price'] * $passengers;

                $availableFlights[] = $flightData;
            }
        }

        usort($availableFlights, function ($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        return $availableFlights;
    }

    /**
     * Get flight details by ID
     */
    public function getFlightDetails(string $flightId): ?array
    {
        return $this->flights[$flightId] ?? null;
    }

    /**
     * Get available airports
     */
    public function getAirports(): array
    {
        return array_values($this->airports);
    }
}