<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'passport_number',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
