<?php

namespace App\Http\Controllers;

use App\Services\EmissionsReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmissionsReportingController extends Controller
{
    public function __construct(
        private EmissionsReportingService $emissionsReportingService
    ) {}

    /**
     * Get emissions summary for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmissionsSummary(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'period' => 'sometimes|string|in:daily,weekly,monthly,yearly',
                'start_date' => 'sometimes|date_format:Y-m-d',
                'end_date' => 'sometimes|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $user = auth()->user();
            $period = $validatedData['period'] ?? 'monthly';

            // If date range is provided, use date range method
            if (isset($validatedData['start_date']) && isset($validatedData['end_date'])) {
                $startDate = \Carbon\Carbon::parse($validatedData['start_date']);
                $endDate = \Carbon\Carbon::parse($validatedData['end_date']);

                $summary = $this->emissionsReportingService->getEmissionsSummaryByDateRange(
                    $user,
                    $startDate,
                    $endDate,
                    $period
                );
            } else {
                $summary = $this->emissionsReportingService->getEmissionsSummary($user, $period);
            }

            return response()->json([
                'success' => true,
                'data' => $summary
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
                'message' => 'An error occurred while generating the emissions summary'
            ], 500);
        }
    }

    /**
     * Clear cache for the authenticated user
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            $user = auth()->user();
            $this->emissionsReportingService->clearUserCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while clearing the cache'
            ], 500);
        }
    }
}