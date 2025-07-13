<?php

namespace App\Models;

use App\DataTransferObjects\FlightData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'flight_id',
        'airline',
        'flight_number',
        'from',
        'to',
        'departure_time',
        'arrival_time',
        'duration',
        'price',
        'seats_available',
        'aircraft',
        'carbon_footprint',
        'eco_rating',
        'date',
        'total_price',
    ];

    /**
     * Create or find FlightDetail from FlightData DTO
     *
     * @param FlightData $flightData
     * @param string|null $date
     * @return FlightDetail
     */
    public static function fromFlightData(FlightData $flightData, ?string $date = null): FlightDetail
    {
        $date = $date ?? $flightData->date ?? now()->toDateString();

        return static::firstOrCreate(
            [
                'flight_id' => $flightData->id,
                'date' => $date,
            ],
            [
                'airline' => $flightData->airline,
                'flight_number' => $flightData->flight_number,
                'from' => $flightData->from,
                'to' => $flightData->to,
                'departure_time' => $flightData->departure_time,
                'arrival_time' => $flightData->arrival_time,
                'duration' => $flightData->duration,
                'price' => $flightData->price,
                'seats_available' => $flightData->seats_available,
                'aircraft' => $flightData->aircraft,
                'carbon_footprint' => $flightData->carbon_footprint,
                'eco_rating' => $flightData->eco_rating,
                'total_price' => $flightData->total_price,
            ]
        );
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'flight_details_id');
    }
}
