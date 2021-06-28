<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public function from_flight()
    {
        return $this->belongsTo(Flight::class, 'flight_from', 'id');
    }

    public function to_flight()
    {
        return $this->belongsTo(Flight::class, 'flight_back', 'id');
    }

    public function flights()
    {
        return Flight::where('id', $this->flight_from)
                        ->orWhere('id', $this->flight_back)
                        ->get();
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class, 'booking_id', 'id');
    }

    protected $guarded = [];

}
