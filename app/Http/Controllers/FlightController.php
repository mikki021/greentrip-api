<?php

namespace App\Http\Controllers;

use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

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

            $booking = $this->flightService->bookFlight($bookingData);

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
}