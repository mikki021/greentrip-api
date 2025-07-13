<?php

namespace App\Http\Controllers;

use App\Services\EmissionCalculatorService;
use App\Services\FlightService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmissionController extends Controller
{
    public function calculate(Request $request, EmissionCalculatorService $emissionCalculator, FlightService $flightService): JsonResponse
    {
        $data = $request->validate([
            'from' => 'required|string|size:3|alpha',
            'to' => 'required|string|size:3|alpha|different:from',
            'class' => 'required|string',
            'passengers' => 'required|integer|min:1|max:1000',
        ]);

        $from = strtoupper($data['from']);
        $to = strtoupper($data['to']);
        $class = strtolower($data['class']);
        $passengers = $data['passengers'];

        $airports = $flightService->getAirports();
        $airportsMap = [];

        foreach ($airports as $airport) {
            $airportsMap[$airport['code']] = $airport;
        }

        if (!isset($airportsMap[$from]) || !isset($airportsMap[$to])) {
            return response()->json([
                'message' => 'Unknown IATA code(s).',
                'from' => $from,
                'to' => $to
            ], 422);
        }

        $lat1 = $airportsMap[$from]['latitude'];
        $lon1 = $airportsMap[$from]['longitude'];
        $lat2 = $airportsMap[$to]['latitude'];
        $lon2 = $airportsMap[$to]['longitude'];

        try {
            $distance = $emissionCalculator->calculateDistance($lat1, $lon1, $lat2, $lon2);
            $emissions = $emissionCalculator->calculateEmissions($distance, $class, $passengers);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json([
            'from' => $from,
            'to' => $to,
            'class' => $class,
            'passengers' => $passengers,
            'distance_km' => round($distance, 2),
            'emissions_kg' => $emissions
        ]);
    }
}