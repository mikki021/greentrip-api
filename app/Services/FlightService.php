<?php

namespace App\Services;

use App\Contracts\FlightProviderInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FlightService
{
    public function __construct(
        private FlightProviderInterface $flightProvider
    ) {}

    /**
     * Search for available flights
     *
     * @param array $searchCriteria
     * @return array
     * @throws ValidationException
     */
    public function searchFlights(array $searchCriteria): array
    {
        $this->validateSearchCriteria($searchCriteria);

        $flights = $this->flightProvider->searchFlights(
            $searchCriteria['from'],
            $searchCriteria['to'],
            $searchCriteria['date'],
            $searchCriteria['passengers'] ?? 1
        );

        return [
            'flights' => $flights,
            'search_criteria' => $searchCriteria,
            'total_count' => count($flights),
            'search_timestamp' => now()->toISOString()
        ];
    }

    /**
     * Book a flight
     *
     * @param array $bookingData
     * @return array
     * @throws ValidationException
     */
    public function bookFlight(array $bookingData): array
    {
        $this->validateBookingData($bookingData);

        $flight = $this->flightProvider->getFlightDetails($bookingData['flight_id']);

        if (!$flight) {
            throw new ValidationException(
                Validator::make([], []),
                'Flight not found'
            );
        }

        if ($flight['seats_available'] < $bookingData['passengers']) {
            throw new ValidationException(
                Validator::make([], []),
                'Insufficient seats available for this flight'
            );
        }

        $bookingReference = $this->generateBookingReference();

        $totalPrice = $flight['price'] * $bookingData['passengers'];

        $booking = [
            'booking_reference' => $bookingReference,
            'flight_id' => $bookingData['flight_id'],
            'flight_details' => $flight,
            'passengers' => $bookingData['passengers'],
            'total_price' => $totalPrice,
            'passenger_details' => $bookingData['passenger_details'],
            'contact_email' => $bookingData['contact_email'],
            'contact_phone' => $bookingData['contact_phone'] ?? null,
            'booking_date' => now()->toISOString(),
            'status' => 'confirmed',
            'carbon_offset_contribution' => $this->calculateCarbonOffset($flight, $bookingData['passengers'])
        ];

        return $booking;
    }

    /**
     * Get available airports
     *
     * @return array
     */
    public function getAirports(): array
    {
        return $this->flightProvider->getAirports();
    }

    /**
     * Get flight details
     *
     * @param string $flightId
     * @return array|null
     */
    public function getFlightDetails(string $flightId): ?array
    {
        return $this->flightProvider->getFlightDetails($flightId);
    }

    /**
     * Validate search criteria
     *
     * @param array $searchCriteria
     * @throws ValidationException
     */
    private function validateSearchCriteria(array $searchCriteria): void
    {
        $validator = Validator::make($searchCriteria, [
            'from' => 'required|string|max:3|regex:/^[A-Z]{3}$/',
            'to' => 'required|string|max:3|regex:/^[A-Z]{3}$/',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'passengers' => 'integer|min:1|max:10'
        ], [
            'from.regex' => 'Origin airport code must be 3 uppercase letters',
            'to.regex' => 'Destination airport code must be 3 uppercase letters',
            'date.after_or_equal' => 'Flight date must be today or in the future',
            'passengers.min' => 'At least 1 passenger is required',
            'passengers.max' => 'Maximum 10 passengers allowed per booking'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($searchCriteria['from'] === $searchCriteria['to']) {
            throw new ValidationException(
                Validator::make([], []),
                'Origin and destination airports must be different'
            );
        }
    }

    /**
     * Validate booking data
     *
     * @param array $bookingData
     * @throws ValidationException
     */
    private function validateBookingData(array $bookingData): void
    {
        $validator = Validator::make($bookingData, [
            'flight_id' => 'required|string',
            'passengers' => 'required|integer|min:1|max:10',
            'passenger_details' => 'required|array|min:1',
            'passenger_details.*.first_name' => 'required|string|max:50',
            'passenger_details.*.last_name' => 'required|string|max:50',
            'passenger_details.*.date_of_birth' => 'required|date_format:Y-m-d|before:today',
            'passenger_details.*.passport_number' => 'required|string|max:20',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:20'
        ], [
            'passengers.min' => 'At least 1 passenger is required',
            'passengers.max' => 'Maximum 10 passengers allowed per booking',
            'passenger_details.min' => 'At least one passenger must be specified',
            'passenger_details.*.first_name.required' => 'First name is required for all passengers',
            'passenger_details.*.last_name.required' => 'Last name is required for all passengers',
            'passenger_details.*.date_of_birth.required' => 'Date of birth is required for all passengers',
            'passenger_details.*.date_of_birth.before' => 'Date of birth must be in the past',
            'passenger_details.*.passport_number.required' => 'Passport number is required for all passengers',
            'contact_email.required' => 'Contact email is required',
            'contact_email.email' => 'Contact email must be a valid email address'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (count($bookingData['passenger_details']) !== $bookingData['passengers']) {
            throw new ValidationException(
                Validator::make([], []),
                'Number of passengers must match the number of passenger details provided'
            );
        }
    }

    /**
     * Generate a unique booking reference
     *
     * @return string
     */
    private function generateBookingReference(): string
    {
        return 'GT' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Calculate carbon offset contribution
     *
     * @param array $flight
     * @param int $passengers
     * @return float
     */
    private function calculateCarbonOffset(array $flight, int $passengers): float
    {
        $carbonPerPassenger = $flight['carbon_footprint'] / $passengers;
        $offsetPercentage = 0.15;

        return round($carbonPerPassenger * $offsetPercentage, 2);
    }
}