<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{

    public function show(Request $request)
    {

        $airports = Airport::select('city', 'iata')
                ->where('city', $request->input('query'))
                ->orWhere('name', $request->input('query'))
                ->orWhere('iata', $request->input('query'))
                ->get();

        return response([
            'data' => [
                'items' => $airports
            ]
        ], 200)->header('Content-Type', 'application/json');

    }

}
