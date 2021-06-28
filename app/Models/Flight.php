<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    public function from_airport()
    {
        return $this->hasOne(Airport::class, 'id', 'from_id');
    }

    public function to_airport()
    {
        return $this->hasOne(Airport::class, 'id', 'to_id');
    }

    public function bookings_from()
    {
        return $this->hasMany(Booking::class, 'flight_from', 'id');
    }

    public function bookings_to()
    {
        return $this->hasMany(Booking::class, 'flight_back', 'id');
    }

    public function bookings()
    {
        return Booking::where('flight_from', $this->id)
                        ->orWhere('flight_back', $this->id)
                        ->get();
    }

}
