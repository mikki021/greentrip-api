<?php

namespace Tests\Unit;

use App\Services\EmissionCalculatorService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmissionCalculatorServiceTest extends TestCase
{
    private EmissionCalculatorService $emissionCalculator;

    protected function setUp(): void
    {
        $this->emissionCalculator = new EmissionCalculatorService();
    }

    /**
     * Test distance calculation using Haversine formula
     */
    public function testCalculateDistance(): void
    {
        // London to Paris (approximately 344 km)
        $distance = $this->emissionCalculator->calculateDistance(51.5074, -0.1278, 48.8566, 2.3522);
        $this->assertEquals(344.0, round($distance), 'London to Paris distance should be approximately 344km', 10);

        // New York to London (approximately 5570 km)
        $distance = $this->emissionCalculator->calculateDistance(40.7128, -74.0060, 51.5074, -0.1278);
        $this->assertEquals(5570.0, round($distance), 'New York to London distance should be approximately 5570km', 50);

        // Same point should return 0
        $distance = $this->emissionCalculator->calculateDistance(51.5074, -0.1278, 51.5074, -0.1278);
        $this->assertEquals(0.0, $distance, 'Distance between same points should be 0');
    }

    /**
     * Test class multipliers
     */
    public function testGetClassMultiplier(): void
    {
        $this->assertEquals(1.0, $this->emissionCalculator->getClassMultiplier('economy'));
        $this->assertEquals(1.2, $this->emissionCalculator->getClassMultiplier('premium_economy'));
        $this->assertEquals(1.5, $this->emissionCalculator->getClassMultiplier('business'));
        $this->assertEquals(2.0, $this->emissionCalculator->getClassMultiplier('first'));
        $this->assertEquals(1.5, $this->emissionCalculator->getClassMultiplier('BUSINESS'));
        $this->assertEquals(2.0, $this->emissionCalculator->getClassMultiplier('First'));
    }

    /**
     * Test invalid class throws exception
     */
    public function testGetClassMultiplierWithInvalidClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid travel class. Must be one of: economy, premium_economy, business, first');

        $this->emissionCalculator->getClassMultiplier('invalid_class');
    }

    /**
     * Test available classes
     */
    public function testGetAvailableClasses(): void
    {
        $expectedClasses = ['economy', 'premium_economy', 'business', 'first'];
        $this->assertEquals($expectedClasses, $this->emissionCalculator->getAvailableClasses());
    }

    /**
     * Test short haul emissions calculation
     */
    public function testCalculateShortHaulEmissions(): void
    {
        $distance = 500; // km

        // Economy class: 500 * 0.255 * 1.0 = 127.5 kg CO2
        $emissions = $this->emissionCalculator->calculateShortHaulEmissions($distance, 'economy');
        $this->assertEquals(127.5, $emissions);

        // Business class: 500 * 0.255 * 1.5 = 191.25 kg CO2
        $emissions = $this->emissionCalculator->calculateShortHaulEmissions($distance, 'business');
        $this->assertEquals(191.25, $emissions);

        // First class: 500 * 0.255 * 2.0 = 255.0 kg CO2
        $emissions = $this->emissionCalculator->calculateShortHaulEmissions($distance, 'first');
        $this->assertEquals(255.0, $emissions);

        // Multiple passengers: 127.5 * 3 = 382.5 kg CO2
        $emissions = $this->emissionCalculator->calculateShortHaulEmissions($distance, 'economy', 3);
        $this->assertEquals(382.5, $emissions);
    }

    /**
     * Test long haul emissions calculation
     */
    public function testCalculateLongHaulEmissions(): void
    {
        $distance = 2000; // km

        // Economy class: 2000 * 0.180 * 1.0 = 360.0 kg CO2
        $emissions = $this->emissionCalculator->calculateLongHaulEmissions($distance, 'economy');
        $this->assertEquals(360.0, $emissions);

        // Business class: 2000 * 0.180 * 1.5 = 540.0 kg CO2
        $emissions = $this->emissionCalculator->calculateLongHaulEmissions($distance, 'business');
        $this->assertEquals(540.0, $emissions);

        // First class: 2000 * 0.180 * 2.0 = 720.0 kg CO2
        $emissions = $this->emissionCalculator->calculateLongHaulEmissions($distance, 'first');
        $this->assertEquals(720.0, $emissions);

        // Multiple passengers: 360.0 * 2 = 720.0 kg CO2
        $emissions = $this->emissionCalculator->calculateLongHaulEmissions($distance, 'economy', 2);
        $this->assertEquals(720.0, $emissions);
    }

    /**
     * Test short haul emissions with invalid distance
     */
    public function testCalculateShortHaulEmissionsWithInvalidDistance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be less than 1500km for short haul flights');

        $this->emissionCalculator->calculateShortHaulEmissions(1500, 'economy');
    }

    /**
     * Test long haul emissions with invalid distance
     */
    public function testCalculateLongHaulEmissionsWithInvalidDistance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be at least 1500km for long haul flights');

        $this->emissionCalculator->calculateLongHaulEmissions(1499, 'economy');
    }

    /**
     * Test general emissions calculation
     */
    public function testCalculateEmissions(): void
    {
        // Short haul: 800 * 0.255 * 1.0 = 204.0 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(800, 'economy');
        $this->assertEquals(204.0, $emissions);

        // Long haul: 3000 * 0.180 * 1.5 = 810.0 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(3000, 'business');
        $this->assertEquals(810.0, $emissions);

        // Premium economy: 1000 * 0.255 * 1.2 * 2 = 612.0 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(1000, 'premium_economy', 2);
        $this->assertEquals(612.0, $emissions);
    }

    /**
     * Test round trip emissions calculation
     */
    public function testCalculateRoundTripEmissions(): void
    {
        $distance = 1000; // km

        // Economy class one-way: 1000 * 0.255 * 1.0 = 255.0 kg CO2
        // Round trip: 255.0 * 2 = 510.0 kg CO2
        $emissions = $this->emissionCalculator->calculateRoundTripEmissions($distance, 'economy');
        $this->assertEquals(510.0, $emissions);

        // Business class with multiple passengers: (1000 * 0.255 * 1.5 * 3) * 2 = 2295.0 kg CO2
        $emissions = $this->emissionCalculator->calculateRoundTripEmissions($distance, 'business', 3);
        $this->assertEquals(2295.0, $emissions);
    }

    /**
     * Test input validation
     */
    public function testInputValidation(): void
    {
        // Invalid distance
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be greater than 0');
        $this->emissionCalculator->calculateEmissions(0, 'economy');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be greater than 0');
        $this->emissionCalculator->calculateEmissions(-100, 'economy');

        // Invalid passengers
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Number of passengers must be greater than 0');
        $this->emissionCalculator->calculateEmissions(1000, 'economy', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Number of passengers cannot exceed 1000');
        $this->emissionCalculator->calculateEmissions(1000, 'economy', 1001);
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Very short distance: 1 * 0.255 * 1.0 = 0.255, rounded to 0.26
        $emissions = $this->emissionCalculator->calculateEmissions(1, 'economy');
        $this->assertEquals(0.26, $emissions);

        // Very long distance: 10000 * 0.180 * 2.0 = 3600.0 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(10000, 'first');
        $this->assertEquals(3600.0, $emissions);

        // Boundary case for short/long haul threshold:
        // 1499 * 0.255 * 1.0 = 382.25 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(1499, 'economy');
        $this->assertEquals(382.25, $emissions);

        // 1500 * 0.180 * 1.0 = 270.0 kg CO2
        $emissions = $this->emissionCalculator->calculateEmissions(1500, 'economy');
        $this->assertEquals(270.0, $emissions);
    }

    /**
     * Test realistic flight scenarios
     */
    public function testRealisticFlightScenarios(): void
    {
        // London to Paris (344 km) - Economy class
        $distance = $this->emissionCalculator->calculateDistance(51.5074, -0.1278, 48.8566, 2.3522);
        $emissions = $this->emissionCalculator->calculateEmissions($distance, 'economy');
        $this->assertGreaterThan(80, $emissions);
        $this->assertLessThan(90, $emissions);

        // New York to London (5570 km) - Business class
        $distance = $this->emissionCalculator->calculateDistance(40.7128, -74.0060, 51.5074, -0.1278);
        $emissions = $this->emissionCalculator->calculateEmissions($distance, 'business');
        $this->assertGreaterThan(1500, $emissions);
        $this->assertLessThan(1600, $emissions);

        // Round trip New York to London - First class
        $distance = $this->emissionCalculator->calculateDistance(40.7128, -74.0060, 51.5074, -0.1278);
        $emissions = $this->emissionCalculator->calculateRoundTripEmissions($distance, 'first');
        $this->assertGreaterThan(4000, $emissions);
        $this->assertLessThan(4100, $emissions);
    }
}