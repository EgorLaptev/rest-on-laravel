<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{

    public function show(Request $request)
    {

        /* Validate data */
        $validator = Validator::make($request->all(), [
            'from' => 'required|exists:airports,iata',
            'to' => 'required|exists:airports,iata',
            'date1' => 'required|regex:/\d\d\d\d-\d\d-\d\d/',
            'date2' => 'nullable|regex:/\d\d\d\d-\d\d-\d\d/',
            'passengers' => 'required|integer|min:1|max:8',
        ]);

        /* If validation failed */
        if ($validator->fails()) {

            return response([
                'error' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422)->header('Content-Type', 'application/json');

        }

        /* Search airports */
        $from_airport = Airport::where('iata', $request->input('from'))->first();
        $to_airport = Airport::where('iata', $request->input('to'))->first();

        /* Search flights */
        $flights_to = Flight::where('from_id', $from_airport['id'])
            ->where('to_id', $to_airport['id'])
            ->get();
        $flights_back = Flight::where('from_id', $to_airport['id'])
            ->where('to_id', $from_airport['id'])
            ->get();

        /* Build response*/
        $response = [
            'data' => []
        ];

        /* Flights to */
        foreach ($flights_to as $flight) {

            /* Passengers count */
            $availability = 165;

            foreach ($flight->bookings_from as $booking)
                $availability -= $booking->passengers->count();

            foreach ($flight->bookings_to as $booking)
                $availability -= $booking->passengers->count();

            $response['data']['flights_to'] =
                [
                    'flight_id' => $flight['id'],
                    'flight_code' => $flight['flight_code'],
                    'from' => [
                        'city' => $flight->from_airport['city'],
                        'airport' => $flight->from_airport['name'],
                        'iata' => $flight->from_airport['iata'],
                        'date' => $validator->validated()['date1'],
                        'time' => $flight['time_from'],
                    ],
                    'to' => [
                        'city' => $flight->to_airport['city'],
                        'airport' => $flight->to_airport['name'],
                        'iata' => $flight->to_airport['iata'],
                        'date' => $validator->validated()['date1'],
                        'time' => $flight['time_to'],
                    ],
                    'cost' => $flight['cost'],
                    'availability' => $availability
                ];

        }

        /* Flights back */
        foreach ($flights_back as $flight) {

            /* Passengers count */
            $availability = 165;

            foreach ($flight->bookings_from as $booking)
                $availability -= $booking->passengers->count();

            foreach ($flight->bookings_to as $booking)
                $availability -= $booking->passengers->count();

            $response['data']['flights_back'] =
                [
                    'flight_id' => $flight['id'],
                    'flight_code' => $flight['flight_code'],
                    'from' => [
                        'city' => $flight->from_airport['city'],
                        'airport' => $flight->from_airport['name'],
                        'iata' => $flight->from_airport['iata'],
                        'date' => $validator->validated()['date1'],
                        'time' => $flight['time_from'],
                    ],
                    'to' => [
                        'city' => $flight->to_airport['city'],
                        'airport' => $flight->to_airport['name'],
                        'iata' => $flight->to_airport['iata'],
                        'date' => $validator->validated()['date1'],
                        'time' => $flight['time_to'],
                    ],
                    'cost' => $flight['cost'],
                    'availability' => $availability
                ];

        }

        return response($response, 200);

    }

}
