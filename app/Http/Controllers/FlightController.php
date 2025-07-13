<?php

namespace App\Http\Controllers;

use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Booking;

class FlightController extends Controller
{
    public function __construct(
        private FlightService $flightService
    ) {}

    /**
     * Search for flights
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchCriteria = $request->only(['from', 'to', 'date', 'passengers']);

            $result = $this->flightService->searchFlights($searchCriteria);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching flights'
            ], 500);
        }
    }

    /**
     * Book a flight
     */
    public function book(Request $request): JsonResponse
    {
        try {
            $bookingData = $request->all();
            $user = auth()->user();

            $booking = $this->flightService->bookFlight($bookingData, $user);

            return response()->json([
                'success' => true,
                'message' => 'Flight booked successfully',
                'data' => $booking
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the flight'
            ], 500);
        }
    }

    /**
     * Get flight details
     */
    public function show(string $flightId): JsonResponse
    {
        try {
            $flight = $this->flightService->getFlightDetails($flightId);

            if (!$flight) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flight not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $flight
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving flight details'
            ], 500);
        }
    }

    /**
     * Get available airports
     */
    public function airports(): JsonResponse
    {
        try {
            $airports = $this->flightService->getAirports();

            return response()->json([
                'success' => true,
                'data' => $airports,
                'count' => count($airports)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving airports'
            ], 500);
        }
    }

    /**
     * Get user's bookings
     */
    public function userBookings(): JsonResponse
    {
        try {
            $user = auth()->user();
            $this->authorize('viewAny', Booking::class);

            $bookings = $user->bookings()->with(['flightDetail', 'passengers'])->get();

            return response()->json([
                'success' => true,
                'data' => $bookings->map(function ($booking) {
                                    return [
                    'id' => $booking->id,
                    'booking_reference' => 'GT' . strtoupper(substr(md5($booking->id), 0, 8)),
                    'flight_details' => $booking->flightDetail,
                    'passengers' => $booking->passengers->map(function ($passenger) {
                        return [
                            'id' => $passenger->id,
                            'first_name' => $passenger->first_name,
                            'last_name' => $passenger->last_name,
                            'date_of_birth' => $passenger->date_of_birth->format('Y-m-d'),
                            'passport_number' => $passenger->passport_number,
                        ];
                    }),
                    'emissions' => $booking->emissions,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at
                ];
                }),
                'count' => $bookings->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving bookings'
            ], 500);
        }
    }

    /**
     * Get specific booking details
     */
    public function showBooking(string $bookingId): JsonResponse
    {
        try {
            $user = auth()->user();
            $booking = $user->bookings()->with(['flightDetail', 'passengers'])->find($bookingId);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $this->authorize('view', $booking);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $booking->id,
                    'booking_reference' => 'GT' . strtoupper(substr(md5($booking->id), 0, 8)),
                    'flight_details' => $booking->flightDetail,
                    'passengers' => $booking->passengers->map(function ($passenger) {
                        return [
                            'id' => $passenger->id,
                            'first_name' => $passenger->first_name,
                            'last_name' => $passenger->last_name,
                            'date_of_birth' => $passenger->date_of_birth->format('Y-m-d'),
                            'passport_number' => $passenger->passport_number,
                        ];
                    }),
                    'emissions' => $booking->emissions,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving booking details'
            ], 500);
        }
    }

    /**
     * Cancel a booking (soft delete)
     */
    public function cancelBooking(string $bookingId): JsonResponse
    {
        try {
            $user = auth()->user();
            $booking = $user->bookings()->with(['flightDetail', 'passengers'])->find($bookingId);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $this->authorize('delete', $booking);

            $booking->update(['status' => 'cancelled']);
            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the booking'
            ], 500);
        }
    }
}