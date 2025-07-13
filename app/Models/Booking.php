<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Booking Model
 *
 * Database Indexes:
 * - Primary: id (auto)
 * - Foreign Keys: user_id, flight_details_id
 * - Performance Indexes:
 *   - bookings_user_created_at_index: (user_id, created_at) - For user booking history
 *   - bookings_user_status_index: (user_id, status) - For booking status queries
 *   - bookings_created_at_index: (created_at) - For date range queries
 *   - bookings_emissions_index: (emissions) - For emissions reporting
 *   - bookings_emissions_reporting_index: (user_id, created_at, emissions) - For emissions summary
 *   - bookings_user_status_date_index: (user_id, status, created_at) - For filtered booking lists
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'flight_details_id',
        'emissions',
        'status',
    ];

    /**
     * Get the user that owns the booking
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the flight details for this booking
     */
    public function flightDetail()
    {
        return $this->belongsTo(FlightDetail::class, 'flight_details_id');
    }

    /**
     * Get the passengers for this booking
     */
    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    /**
     * Scope for user bookings with optimized query
     * Uses bookings_user_created_at_index
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for emissions reporting with optimized query
     * Uses bookings_emissions_reporting_index
     */
    public function scopeForEmissionsReport($query, $userId, $startDate = null, $endDate = null)
    {
        $query->where('user_id', $userId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
