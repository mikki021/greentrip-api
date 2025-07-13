<?php

namespace App\Services;

class EmissionCalculatorService
{
    /**
     * Base emission rates in kg CO2 per passenger per km
     */
    private const BASE_EMISSION_RATE_SHORT_HAUL = 0.255; // kg CO2/passenger/km
    private const BASE_EMISSION_RATE_LONG_HAUL = 0.180;  // kg CO2/passenger/km

    /**
     * Distance thresholds in km
     */
    private const SHORT_HAUL_THRESHOLD = 1500; // km

    /**
     * Class multipliers
     */
    private const CLASS_MULTIPLIERS = [
        'economy' => 1.0,
        'premium_economy' => 1.2,
        'business' => 1.5,
        'first' => 2.0
    ];

    /**
     * Earth's radius in kilometers
     */
    private const EARTH_RADIUS = 6371;

    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param float $lat1 Latitude of point 1 in degrees
     * @param float $lon1 Longitude of point 1 in degrees
     * @param float $lat2 Latitude of point 2 in degrees
     * @param float $lon2 Longitude of point 2 in degrees
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS * $c;
    }

    /**
     * Calculate emissions for a flight
     *
     * @param float $distance Distance in kilometers
     * @param string $class Travel class (economy, premium_economy, business, first)
     * @param int $passengers Number of passengers
     * @return float Total emissions in kg CO2
     */
    public function calculateEmissions(float $distance, string $class, int $passengers = 1): float
    {
        $this->validateInputs($distance, $class, $passengers);

        $baseEmissionRate = $this->getBaseEmissionRate($distance);
        $classMultiplier = $this->getClassMultiplier($class);

        $emissionsPerPassenger = $distance * $baseEmissionRate * $classMultiplier;
        $totalEmissions = $emissionsPerPassenger * $passengers;

        return round($totalEmissions, 2);
    }

    /**
     * Calculate emissions for short haul flights (< 1500km)
     *
     * @param float $distance Distance in kilometers
     * @param string $class Travel class
     * @param int $passengers Number of passengers
     * @return float Total emissions in kg CO2
     */
    public function calculateShortHaulEmissions(float $distance, string $class, int $passengers = 1): float
    {
        if ($distance >= self::SHORT_HAUL_THRESHOLD) {
            throw new \InvalidArgumentException('Distance must be less than ' . self::SHORT_HAUL_THRESHOLD . 'km for short haul flights');
        }

        return $this->calculateEmissions($distance, $class, $passengers);
    }

    /**
     * Calculate emissions for long haul flights (≥ 1500km)
     *
     * @param float $distance Distance in kilometers
     * @param string $class Travel class
     * @param int $passengers Number of passengers
     * @return float Total emissions in kg CO2
     */
    public function calculateLongHaulEmissions(float $distance, string $class, int $passengers = 1): float
    {
        if ($distance < self::SHORT_HAUL_THRESHOLD) {
            throw new \InvalidArgumentException('Distance must be at least ' . self::SHORT_HAUL_THRESHOLD . 'km for long haul flights');
        }

        return $this->calculateEmissions($distance, $class, $passengers);
    }

    /**
     * Get base emission rate based on distance
     *
     * @param float $distance Distance in kilometers
     * @return float Base emission rate
     */
    private function getBaseEmissionRate(float $distance): float
    {
        return $distance < self::SHORT_HAUL_THRESHOLD
            ? self::BASE_EMISSION_RATE_SHORT_HAUL
            : self::BASE_EMISSION_RATE_LONG_HAUL;
    }

    /**
     * Get class multiplier
     *
     * @param string $class Travel class
     * @return float Class multiplier
     */
    public function getClassMultiplier(string $class): float
    {
        $class = strtolower($class);

        if (!isset(self::CLASS_MULTIPLIERS[$class])) {
            throw new \InvalidArgumentException(
                'Invalid travel class. Must be one of: ' . implode(', ', array_keys(self::CLASS_MULTIPLIERS))
            );
        }

        return self::CLASS_MULTIPLIERS[$class];
    }

    /**
     * Get available travel classes
     *
     * @return array
     */
    public function getAvailableClasses(): array
    {
        return array_keys(self::CLASS_MULTIPLIERS);
    }

    /**
     * Calculate emissions for a round trip
     *
     * @param float $distance One-way distance in kilometers
     * @param string $class Travel class
     * @param int $passengers Number of passengers
     * @return float Total round trip emissions in kg CO2
     */
    public function calculateRoundTripEmissions(float $distance, string $class, int $passengers = 1): float
    {
        $oneWayEmissions = $this->calculateEmissions($distance, $class, $passengers);
        return round($oneWayEmissions * 2, 2);
    }

    /**
     * Validate input parameters
     *
     * @param float $distance
     * @param string $class
     * @param int $passengers
     * @throws \InvalidArgumentException
     */
    private function validateInputs(float $distance, string $class, int $passengers): void
    {
        if ($distance <= 0) {
            throw new \InvalidArgumentException('Distance must be greater than 0');
        }

        if ($passengers <= 0) {
            throw new \InvalidArgumentException('Number of passengers must be greater than 0');
        }

        if ($passengers > 1000) {
            throw new \InvalidArgumentException('Number of passengers cannot exceed 1000');
        }

        $this->getClassMultiplier($class);
    }
}