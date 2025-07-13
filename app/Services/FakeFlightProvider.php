<?php

namespace App\Services;

use App\Contracts\FlightProviderInterface;
use App\Services\DTOs\FlightData;
use App\Services\DTOs\AirportData;

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
     * Static airports data with coordinates
     */
    private array $airports = [
        'JFK' => [
            'code' => 'JFK',
            'name' => 'John F. Kennedy International Airport',
            'city' => 'New York',
            'country' => 'USA',
            'latitude' => 40.6413,
            'longitude' => -73.7781
        ],
        'LAX' => [
            'code' => 'LAX',
            'name' => 'Los Angeles International Airport',
            'city' => 'Los Angeles',
            'country' => 'USA',
            'latitude' => 33.9416,
            'longitude' => -118.4085
        ],
        'ORD' => [
            'code' => 'ORD',
            'name' => 'O\'Hare International Airport',
            'city' => 'Chicago',
            'country' => 'USA',
            'latitude' => 41.9742,
            'longitude' => -87.9073
        ],
        'SFO' => [
            'code' => 'SFO',
            'name' => 'San Francisco International Airport',
            'city' => 'San Francisco',
            'country' => 'USA',
            'latitude' => 37.6213,
            'longitude' => -122.3790
        ],
        'MIA' => [
            'code' => 'MIA',
            'name' => 'Miami International Airport',
            'city' => 'Miami',
            'country' => 'USA',
            'latitude' => 25.7932,
            'longitude' => -80.2906
        ],
        'SEA' => [
            'code' => 'SEA',
            'name' => 'Seattle-Tacoma International Airport',
            'city' => 'Seattle',
            'country' => 'USA',
            'latitude' => 47.4502,
            'longitude' => -122.3088
        ],
        'LHR' => [
            'code' => 'LHR',
            'name' => 'London Heathrow Airport',
            'city' => 'London',
            'country' => 'UK',
            'latitude' => 51.4700,
            'longitude' => -0.4543
        ],
        'CDG' => [
            'code' => 'CDG',
            'name' => 'Paris Charles de Gaulle Airport',
            'city' => 'Paris',
            'country' => 'France',
            'latitude' => 49.0097,
            'longitude' => 2.5479
        ],
        'FRA' => [
            'code' => 'FRA',
            'name' => 'Frankfurt Airport',
            'city' => 'Frankfurt',
            'country' => 'Germany',
            'latitude' => 50.0379,
            'longitude' => 8.5622
        ],
        'DXB' => [
            'code' => 'DXB',
            'name' => 'Dubai International Airport',
            'city' => 'Dubai',
            'country' => 'UAE',
            'latitude' => 25.2532,
            'longitude' => 55.3657
        ],
        'HND' => [
            'code' => 'HND',
            'name' => 'Tokyo Haneda Airport',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'latitude' => 35.5494,
            'longitude' => 139.7798
        ],
        'SIN' => [
            'code' => 'SIN',
            'name' => 'Singapore Changi Airport',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.3644,
            'longitude' => 103.9915
        ],
        'AMS' => [
            'code' => 'AMS',
            'name' => 'Amsterdam Airport Schiphol',
            'city' => 'Amsterdam',
            'country' => 'Netherlands',
            'latitude' => 52.3105,
            'longitude' => 4.7683
        ],
        'MAD' => [
            'code' => 'MAD',
            'name' => 'Madrid Barajas Airport',
            'city' => 'Madrid',
            'country' => 'Spain',
            'latitude' => 40.4983,
            'longitude' => -3.5676
        ],
        'ATL' => [
            'code' => 'ATL',
            'name' => 'Hartsfield-Jackson Atlanta International Airport',
            'city' => 'Atlanta',
            'country' => 'USA',
            'latitude' => 33.6407,
            'longitude' => -84.4277
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

                $availableFlights[] = FlightData::fromArray($flightData);
            }
        }

        usort($availableFlights, function (FlightData $a, FlightData $b) {
            return $a->price <=> $b->price;
        });

        return $availableFlights;
    }

    /**
     * Get flight details by ID
     */
    public function getFlightDetails(string $flightId): ?FlightData
    {
        if (!isset($this->flights[$flightId])) {
            return null;
        }

        return FlightData::fromArray($this->flights[$flightId]);
    }

    /**
     * Get available airports
     */
    public function getAirports(): array
    {
        return array_map(fn($airport) => AirportData::fromArray($airport), array_values($this->airports));
    }
}