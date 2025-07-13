<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'flight_details_id',
        'emissions',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flightDetail()
    {
        return $this->belongsTo(FlightDetail::class, 'flight_details_id');
    }
}
