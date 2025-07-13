<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EmissionsReportingService
{
    private const CACHE_TTL = 120; // 2 minutes in seconds

    /**
     * Get emissions summary for a user aggregated by time periods
     *
     * @param User $user
     * @param string $period
     * @return array
     */
    public function getEmissionsSummary(User $user, string $period = 'monthly'): array
    {
        $cacheKey = $this->generateCacheKey($user->id, $period);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $period) {
            return $this->calculateEmissionsSummary($user, $period);
        });
    }

    /**
     * Calculate emissions summary for a user
     *
     * @param User $user
     * @param string $period
     * @return array
     */
    private function calculateEmissionsSummary(User $user, string $period): array
    {
        // Use optimized query with proper indexing
        $query = Booking::withTrashed()
            ->forEmissionsReport($user->id) // Uses bookings_emissions_reporting_index
            ->with(['flightDetail' => function ($query) {
                $query->select('id', 'from', 'to', 'airline', 'date');
            }])
            ->select([
                'id',
                'user_id',
                'flight_details_id',
                'emissions',
                'status',
                'created_at',
                DB::raw('DATE(created_at) as booking_date')
            ]);

        $groupBy = $this->getGroupByClause($period);
        $dateFormat = $this->getDateFormat($period);

        $results = $query->get()
            ->groupBy(function ($booking) use ($groupBy, $dateFormat) {
                return $booking->created_at->format($dateFormat);
            })
            ->map(function ($bookings, $periodKey) {
                return [
                    'period' => $periodKey,
                    'total_emissions' => round($bookings->sum('emissions'), 2),
                    'booking_count' => $bookings->count(),
                    'average_emissions_per_booking' => round($bookings->avg('emissions'), 2),
                    'bookings' => $bookings->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'emissions' => $booking->emissions,
                            'status' => $booking->status,
                            'flight' => [
                                'from' => $booking->flightDetail->from,
                                'to' => $booking->flightDetail->to,
                                'airline' => $booking->flightDetail->airline,
                                'date' => $booking->flightDetail->date,
                            ],
                            'created_at' => $booking->created_at->toISOString(),
                        ];
                    })->values()
                ];
            })
            ->values()
            ->sortBy('period')
            ->toArray();

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'period_type' => $period,
            'total_emissions' => round($query->sum('emissions'), 2),
            'total_bookings' => $query->count(),
            'periods' => $results,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get group by clause based on period
     *
     * @param string $period
     * @return string
     */
    private function getGroupByClause(string $period): string
    {
        return match ($period) {
            'daily' => 'DATE(created_at)',
            'weekly' => 'YEARWEEK(created_at, 1)',
            'monthly' => 'DATE_FORMAT(created_at, "%Y-%m")',
            'yearly' => 'YEAR(created_at)',
            default => 'DATE_FORMAT(created_at, "%Y-%m")',
        };
    }

    /**
     * Get date format for grouping
     *
     * @param string $period
     * @return string
     */
    private function getDateFormat(string $period): string
    {
        return match ($period) {
            'daily' => 'Y-m-d',
            'weekly' => 'Y-W',
            'monthly' => 'Y-m',
            'yearly' => 'Y',
            default => 'Y-m',
        };
    }

    /**
     * Generate cache key for user emissions summary
     *
     * @param int $userId
     * @param string $period
     * @return string
     */
    private function generateCacheKey(int $userId, string $period): string
    {
        return "emissions_summary:user:{$userId}:period:{$period}";
    }

    /**
     * Clear cache for a specific user
     *
     * @param int $userId
     * @return void
     */
    public function clearUserCache(int $userId): void
    {
        $periods = ['daily', 'weekly', 'monthly', 'yearly'];

        foreach ($periods as $period) {
            $cacheKey = $this->generateCacheKey($userId, $period);
            Cache::forget($cacheKey);
        }
    }

    /**
     * Get emissions summary with custom date range
     *
     * @param User $user
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $period
     * @return array
     */
    public function getEmissionsSummaryByDateRange(
        User $user,
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        string $period = 'monthly'
    ): array {
        $cacheKey = $this->generateDateRangeCacheKey($user->id, $startDate, $endDate, $period);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $startDate, $endDate, $period) {
            return $this->calculateEmissionsSummaryByDateRange($user, $startDate, $endDate, $period);
        });
    }

    /**
     * Calculate emissions summary for a custom date range
     *
     * @param User $user
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $period
     * @return array
     */
    private function calculateEmissionsSummaryByDateRange(
        User $user,
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        string $period
    ): array {
        $query = Booking::withTrashed()->where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['flightDetail' => function ($query) {
                $query->select('id', 'from', 'to', 'airline', 'date');
            }])
            ->select([
                'id',
                'user_id',
                'flight_details_id',
                'emissions',
                'status',
                'created_at',
                DB::raw('DATE(created_at) as booking_date')
            ]);

        $dateFormat = $this->getDateFormat($period);

        $results = $query->get()
            ->groupBy(function ($booking) use ($dateFormat) {
                return $booking->created_at->format($dateFormat);
            })
            ->map(function ($bookings, $periodKey) {
                return [
                    'period' => $periodKey,
                    'total_emissions' => round($bookings->sum('emissions'), 2),
                    'booking_count' => $bookings->count(),
                    'average_emissions_per_booking' => round($bookings->avg('emissions'), 2),
                ];
            })
            ->values()
            ->sortBy('period')
            ->toArray();

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'period_type' => $period,
            'date_range' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'total_emissions' => round($query->sum('emissions'), 2),
            'total_bookings' => $query->count(),
            'periods' => $results,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate cache key for date range query
     *
     * @param int $userId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $period
     * @return string
     */
    private function generateDateRangeCacheKey(int $userId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate, string $period): string
    {
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');
        return "emissions_summary:user:{$userId}:range:{$startStr}:{$endStr}:period:{$period}";
    }
}