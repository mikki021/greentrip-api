<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\User;
use App\Services\EmissionsReportingService;
use App\Services\FakeFlightProvider;
use App\Services\EmissionCalculatorService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmissionsReportingServiceTest extends TestCase
{
    private EmissionsReportingService $emissionsReportingService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear the database before each test
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);

        $this->emissionsReportingService = new EmissionsReportingService();
        $this->user = User::factory()->create();

        // Clear cache before each test
        Cache::flush();
    }

    public function test_get_emissions_summary_returns_correct_data(): void
    {
        // Create test bookings
        $this->createTestBookings();

        $summary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        $this->assertArrayHasKey('user_id', $summary);
        $this->assertArrayHasKey('user_name', $summary);
        $this->assertArrayHasKey('period_type', $summary);
        $this->assertArrayHasKey('total_emissions', $summary);
        $this->assertArrayHasKey('total_bookings', $summary);
        $this->assertArrayHasKey('periods', $summary);
        $this->assertArrayHasKey('generated_at', $summary);

        $this->assertEquals($this->user->id, $summary['user_id']);
        $this->assertEquals($this->user->name, $summary['user_name']);
        $this->assertEquals('monthly', $summary['period_type']);
        $this->assertGreaterThan(0, $summary['total_emissions']);
        $this->assertGreaterThan(0, $summary['total_bookings']);
        $this->assertIsArray($summary['periods']);
    }

    public function test_emissions_summary_is_cached(): void
    {
        // Create test bookings
        $this->createTestBookings();

        // First call should cache the result
        $summary1 = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        // Verify cache key exists
        $cacheKey = "emissions_summary:user:{$this->user->id}:period:monthly";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $summary2 = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        $this->assertEquals($summary1, $summary2);
    }

    public function test_cache_is_cleared_for_user(): void
    {
        // Create test bookings
        $this->createTestBookings();

        // Generate cache for different periods
        $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');
        $this->emissionsReportingService->getEmissionsSummary($this->user, 'daily');

        // Verify cache exists
        $this->assertTrue(Cache::has("emissions_summary:user:{$this->user->id}:period:monthly"));
        $this->assertTrue(Cache::has("emissions_summary:user:{$this->user->id}:period:daily"));

        // Clear cache
        $this->emissionsReportingService->clearUserCache($this->user->id);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("emissions_summary:user:{$this->user->id}:period:monthly"));
        $this->assertFalse(Cache::has("emissions_summary:user:{$this->user->id}:period:daily"));
    }

    public function test_different_periods_return_different_groupings(): void
    {
        // Create test bookings
        $this->createTestBookings();

        $monthlySummary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');
        $dailySummary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'daily');

        $this->assertEquals('monthly', $monthlySummary['period_type']);
        $this->assertEquals('daily', $dailySummary['period_type']);

        // Monthly should have fewer periods than daily for the same data
        $this->assertLessThanOrEqual(
            count($dailySummary['periods']),
            count($monthlySummary['periods'])
        );
    }

    public function test_date_range_summary_works_correctly(): void
    {
        // Create test bookings
        $this->createTestBookings();

        $startDate = now()->subDays(30);
        $endDate = now();

        $summary = $this->emissionsReportingService->getEmissionsSummaryByDateRange(
            $this->user,
            $startDate,
            $endDate,
            'monthly'
        );

        $this->assertArrayHasKey('date_range', $summary);
        $this->assertEquals($startDate->toISOString(), $summary['date_range']['start']);
        $this->assertEquals($endDate->toISOString(), $summary['date_range']['end']);
    }

    public function test_date_range_summary_is_cached(): void
    {
        // Create test bookings
        $this->createTestBookings();

        $startDate = now()->subDays(30);
        $endDate = now();

        // First call should cache the result
        $summary1 = $this->emissionsReportingService->getEmissionsSummaryByDateRange(
            $this->user,
            $startDate,
            $endDate,
            'monthly'
        );

        // Second call should return cached result
        $summary2 = $this->emissionsReportingService->getEmissionsSummaryByDateRange(
            $this->user,
            $startDate,
            $endDate,
            'monthly'
        );

        $this->assertEquals($summary1, $summary2);
    }

    public function test_periods_contain_booking_details(): void
    {
        // Create test bookings
        $this->createTestBookings();

        $summary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        foreach ($summary['periods'] as $period) {
            $this->assertArrayHasKey('period', $period);
            $this->assertArrayHasKey('total_emissions', $period);
            $this->assertArrayHasKey('booking_count', $period);
            $this->assertArrayHasKey('average_emissions_per_booking', $period);
            $this->assertArrayHasKey('bookings', $period);

            // Check that bookings array contains detailed information
            foreach ($period['bookings'] as $booking) {
                $this->assertArrayHasKey('id', $booking);
                $this->assertArrayHasKey('emissions', $booking);
                $this->assertArrayHasKey('status', $booking);
                $this->assertArrayHasKey('flight', $booking);
                $this->assertArrayHasKey('created_at', $booking);

                // Check flight details
                $this->assertArrayHasKey('from', $booking['flight']);
                $this->assertArrayHasKey('to', $booking['flight']);
                $this->assertArrayHasKey('airline', $booking['flight']);
                $this->assertArrayHasKey('date', $booking['flight']);
            }
        }
    }

    public function test_empty_user_returns_zero_emissions(): void
    {
        $summary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        $this->assertEquals(0, $summary['total_emissions']);
        $this->assertEquals(0, $summary['total_bookings']);
        $this->assertEmpty($summary['periods']);
    }

    public function test_cancelled_bookings_are_included_in_summary(): void
    {
        // Create a confirmed booking
        $flightDetail = FlightDetail::create([
            'flight_id' => 'FL001',
            'airline' => 'Test Airlines',
            'flight_number' => 'TA101',
            'from' => 'JFK',
            'to' => 'LAX',
            'departure_time' => '10:00',
            'arrival_time' => '13:30',
            'duration' => '3h 30m',
            'price' => 299.99,
            'seats_available' => 100,
            'aircraft' => 'Boeing 737',
            'carbon_footprint' => 0.85,
            'eco_rating' => 4.2,
            'date' => now()->addDays(10)->toDateString(),
            'total_price' => 299.99,
        ]);

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => 1000.0,
            'status' => 'cancelled',
        ]);

        // Soft delete the booking
        $booking->delete();

        $summary = $this->emissionsReportingService->getEmissionsSummary($this->user, 'monthly');

        // Cancelled bookings should still be included in the summary
        $this->assertEquals(1000.0, $summary['total_emissions']);
        $this->assertEquals(1, $summary['total_bookings']);
    }

    /**
     * Create test bookings for the user
     */
    private function createTestBookings(): void
    {
        $flightDetail = FlightDetail::create([
            'flight_id' => 'FL001',
            'airline' => 'Test Airlines',
            'flight_number' => 'TA101',
            'from' => 'JFK',
            'to' => 'LAX',
            'departure_time' => '10:00',
            'arrival_time' => '13:30',
            'duration' => '3h 30m',
            'price' => 299.99,
            'seats_available' => 100,
            'aircraft' => 'Boeing 737',
            'carbon_footprint' => 0.85,
            'eco_rating' => 4.2,
            'date' => now()->addDays(10)->toDateString(),
            'total_price' => 299.99,
        ]);

        // Create bookings on different dates
        Booking::create([
            'user_id' => $this->user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => 1000.0,
            'status' => 'confirmed',
            'created_at' => now()->subDays(5),
        ]);

        Booking::create([
            'user_id' => $this->user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => 1500.0,
            'status' => 'confirmed',
            'created_at' => now()->subDays(10),
        ]);

        Booking::create([
            'user_id' => $this->user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => 2000.0,
            'status' => 'confirmed',
            'created_at' => now()->subDays(15),
        ]);
    }
}