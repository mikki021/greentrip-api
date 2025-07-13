<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Booking;
use App\Models\FlightDetail;
use App\Models\Passenger;
use App\Services\DTOs\FlightData;
use App\Services\DTOs\PassengerData;
use App\Services\EmissionCalculatorService;
use App\Services\FakeFlightProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserWithBookingsSeeder extends Seeder
{
    private FakeFlightProvider $flightProvider;
    private EmissionCalculatorService $emissionCalculator;

    public function __construct()
    {
        $this->flightProvider = new FakeFlightProvider();
        $this->emissionCalculator = new EmissionCalculatorService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 users with random bookings
        User::factory(20)->create()->each(function ($user) {
            $this->createRandomBookingsForUser($user);
        });

        // Cancel approximately 20% of all bookings
        $this->cancelRandomBookings();
    }

    /**
     * Create random bookings for a user
     */
    private function createRandomBookingsForUser(User $user): void
    {
        // Random number of bookings between 1 and 4
        $numberOfBookings = rand(1, 4);

        for ($i = 0; $i < $numberOfBookings; $i++) {
            $this->createRandomBooking($user);
        }
    }

    /**
     * Create a single random booking for a user
     */
    private function createRandomBooking(User $user): void
    {
        // Get random flight data
        $flightIds = ['FL001', 'FL002', 'FL003', 'FL004'];
        $randomFlightId = $flightIds[array_rand($flightIds)];
        $flightData = $this->flightProvider->getFlightDetails($randomFlightId);

        if (!$flightData) {
            return;
        }

        // Create or find flight detail
        $flightDetail = FlightDetail::fromFlightData($flightData, now()->addDays(rand(1, 30))->toDateString());

        // Random number of passengers (1-3)
        $passengerCount = rand(1, 3);

        // Calculate emissions using airport coordinates
        $airports = $this->flightProvider->getAirports();
        $airportsMap = [];
        foreach ($airports as $airport) {
            $airportsMap[$airport->code] = $airport;
        }

        $from = $flightData->from;
        $to = $flightData->to;
        $class = $this->getRandomClass();

        $lat1 = $airportsMap[$from]->latitude ?? null;
        $lon1 = $airportsMap[$from]->longitude ?? null;
        $lat2 = $airportsMap[$to]->latitude ?? null;
        $lon2 = $airportsMap[$to]->longitude ?? null;

        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            // Fallback to a default distance if coordinates are not available
            $distance = 1000.0; // Default 1000km
        } else {
            $distance = $this->emissionCalculator->calculateDistance($lat1, $lon1, $lat2, $lon2);
        }

        $emissions = $this->emissionCalculator->calculateEmissions($distance, $class, $passengerCount);

        // Create booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'flight_details_id' => $flightDetail->id,
            'emissions' => $emissions,
            'status' => 'confirmed',
        ]);

        // Create passengers
        for ($j = 0; $j < $passengerCount; $j++) {
            $this->createRandomPassenger($booking);
        }
    }

    /**
     * Create a random passenger for a booking
     */
    private function createRandomPassenger(Booking $booking): void
    {
        $passengerData = new PassengerData(
            fake()->firstName(),
            fake()->lastName(),
            fake()->date('Y-m-d', '-18 years'),
            'PASS' . strtoupper(fake()->bothify('??????'))
        );

        Passenger::create([
            'booking_id' => $booking->id,
            'first_name' => $passengerData->first_name,
            'last_name' => $passengerData->last_name,
            'date_of_birth' => $passengerData->date_of_birth,
            'passport_number' => $passengerData->passport_number,
        ]);
    }

    /**
     * Get a random flight class
     */
    private function getRandomClass(): string
    {
        $classes = ['economy', 'business', 'first'];
        return $classes[array_rand($classes)];
    }

    /**
     * Cancel approximately 20% of all bookings
     */
    private function cancelRandomBookings(): void
    {
        $totalBookings = Booking::count();
        $bookingsToCancel = (int) round($totalBookings * 0.2); // 20%

        if ($bookingsToCancel > 0) {
            // Get random booking IDs to cancel
            $bookingIds = Booking::inRandomOrder()
                ->limit($bookingsToCancel)
                ->pluck('id')
                ->toArray();

            // Cancel the bookings (soft delete)
            Booking::whereIn('id', $bookingIds)->each(function ($booking) {
                $booking->update(['status' => 'cancelled']);
                $booking->delete(); // Soft delete
            });
        }
    }
}