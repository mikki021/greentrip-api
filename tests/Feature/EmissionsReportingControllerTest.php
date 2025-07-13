<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmissionsReportingControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear the database before each test
        $this->artisan('migrate:fresh', ['--database' => 'mysql_testing']);

        $this->user = User::factory()->create();
        Cache::flush();
    }

    public function test_get_emissions_summary_requires_authentication(): void
    {
        $response = $this->getJson('/api/emissions/summary');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_get_emissions_summary_returns_correct_structure(): void
    {
        $this->createTestBookings();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'user_name',
                    'period_type',
                    'total_emissions',
                    'total_bookings',
                    'periods',
                    'generated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.user_id'));
        $this->assertEquals($this->user->name, $response->json('data.user_name'));
        $this->assertEquals('monthly', $response->json('data.period_type'));
    }

    public function test_get_emissions_summary_with_different_periods(): void
    {
        $this->createTestBookings();

        $periods = ['daily', 'weekly', 'monthly', 'yearly'];

        foreach ($periods as $period) {
            $response = $this->actingAs($this->user, 'api')
                ->getJson("/api/emissions/summary?period={$period}");

            $response->assertStatus(200);
            $this->assertEquals($period, $response->json('data.period_type'));
        }
    }

    public function test_get_emissions_summary_with_date_range(): void
    {
        $this->createTestBookings();

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/emissions/summary?start_date={$startDate}&end_date={$endDate}&period=monthly");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'user_name',
                    'period_type',
                    'date_range',
                    'total_emissions',
                    'total_bookings',
                    'periods',
                    'generated_at'
                ]
            ]);

        $this->assertArrayHasKey('date_range', $response->json('data'));
        $this->assertEquals($startDate, date('Y-m-d', strtotime($response->json('data.date_range.start'))));
        $this->assertEquals($endDate, date('Y-m-d', strtotime($response->json('data.date_range.end'))));
    }

    public function test_get_emissions_summary_validates_date_range(): void
    {
        $startDate = now()->format('Y-m-d');
        $endDate = now()->subDays(1)->format('Y-m-d'); // End date before start date

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/emissions/summary?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_get_emissions_summary_validates_period(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary?period=invalid');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_get_emissions_summary_validates_date_format(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary?start_date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_clear_cache_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/emissions/cache');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_clear_cache_works_correctly(): void
    {
        // Create some cache first
        $this->createTestBookings();

        $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary?period=monthly');

        // Verify cache exists
        $cacheKey = "emissions_summary:user:{$this->user->id}:period:monthly";
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/emissions/cache');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_emissions_summary_includes_booking_details(): void
    {
        $this->createTestBookings();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary');

        $response->assertStatus(200);

        $periods = $response->json('data.periods');
        $this->assertNotEmpty($periods);

        foreach ($periods as $period) {
            $this->assertArrayHasKey('bookings', $period);
            $this->assertIsArray($period['bookings']);

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
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(0, $data['total_emissions']);
        $this->assertEquals(0, $data['total_bookings']);
        $this->assertEmpty($data['periods']);
    }

    public function test_cancelled_bookings_are_included_in_summary(): void
    {
        // Create a flight detail
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

        // Create a cancelled booking
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => 1000.0,
            'status' => 'cancelled',
        ]);

        // Soft delete the booking
        $booking->delete();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/emissions/summary');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(1000.0, $data['total_emissions']);
        $this->assertEquals(1, $data['total_bookings']);
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