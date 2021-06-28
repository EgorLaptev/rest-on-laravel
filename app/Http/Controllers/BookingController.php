<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use voku\helper\ASCII;

class BookingController extends Controller
{

    public function store(Request $request)
    {

        /* Validation */
        $validator = Validator::make($request->all(), [
            'passengers.*.first_name' => 'required|string',
            'passengers.*.last_name' => 'required|string',
            'passengers.*.birth_date' => 'required|date_format:Y-m-d',
            'passengers.*.document_number' => 'required|digits:10'
        ]);

        if ($validator->fails()) {

            return response([

                'error' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]

            ], 422)->header('Content-Type', 'application/json');

        }

        $data = [
            'flight_from' => $request->input('flight_from'),
            'flight_back' => $request->input('flight_back'),
            'passengers' => $request->input('passengers'),
        ];

        $availability = 165;

        foreach (
            Booking::where('flight_from', $data['flight_from']['id'])
                ->where('date_from', $data['flight_from']['date'])
                ->get() as $booking
        ) {
            $availability -= $booking->passengers->count();
        }
        foreach (
            Booking::where('flight_back', $data['flight_back']['id'])
                ->where('date_back', $data['flight_back']['date'])
                ->get() as $booking
        ) {
            $availability -= $booking->passengers->count();
        }

        if (count($data['passengers']) <= $availability) {

            $booking = Booking::create([
                'flight_from' => $data['flight_from']['id'],
                'flight_back' => $data['flight_back']['id'],
                'date_from' => $data['flight_from']['date'],
                'date_back' => $data['flight_back']['date'],
                'code' => substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 5)
            ]);

            foreach ($data['passengers'] as $passenger) {
                $passenger['booking_id'] = $booking['id'];
                Passenger::create($passenger);
            }

            return response([
                'data' => [
                    'code' => $booking['code']
                ]
            ], 201)->header('Content-Type', 'application/json');

        }

    }

    public function show(Request $request, $code)
    {

        $booking = Booking::where('code', $code)->first();

        $flights = [];
        $passengers = [];

        foreach ($booking->flights() as $flight) {

            $availability = 165;

            foreach ($flight->bookings() as $booking) {
                $availability -= $booking->passengers->count();
            }

            $flights[] = [
                'flight_id' => $flight['id'],
                'flight_code' => $flight['flight_code'],
                'from' => [
                    'city' => $flight->from_airport['city'],
                    'airport' => $flight->from_airport['name'],
                    'iata' => $flight->from_airport['iata'],
                    'date' => $booking['date_from'],
                    'time' => $flight['time_from'],
                ],
                'to' => [
                    'city' => $flight->to_airport['city'],
                    'airport' => $flight->to_airport['name'],
                    'iata' => $flight->to_airport['iata'],
                    'date' => $booking['date_back'],
                    'time' => $flight['time_to'],
                ],
                'cost' => $flight['cost'],
                'availability' => $availability
            ];

        }

        foreach ($booking->passengers as $passenger) {

            $passengers[] = [
                'id' => $passenger['id'],
                'first_name' => $passenger['first_name'],
                'last_name' => $passenger['last_name'],
                'birth_date' => $passenger['birth_date'],
                'document_number' => $passenger['document_number'],
                'place_from' => $passenger['place_from'],
                'place_back' => $passenger['place_back']
            ];

        }

        return response([
            'date' => [
                'code' => $code,
                'cost' => $booking->flights()[0]['cost'] + $booking->flights()[1]['cost'],
                'flights' => $flights,
                'passengers' => $passengers,
            ]
        ], 200)->header('Content-Type', 'application/json');

    }

    public function seat($code)
    {

        $booking = Booking::where('code', $code)->first();

        $occupied_from = [];
        $occupied_back = [];

        foreach ($booking->passengers as $passenger) {

            if ($passenger['place_from']) {
                $occupied_from[] = [
                    'passenger_id' => $passenger['id'],
                    'place' => $passenger['place_from']
                ];
            }

            if ($passenger['place_back']) {
                $occupied_back[] = [
                    'passenger_id' => $passenger['id'],
                    'place' => $passenger['place_back']
                ];
            }

        }

        return response([
            'data' => [
                'occupied_from' => $occupied_from,
                'occupied_back' => $occupied_back,
            ]
        ], 200);

    }

    public function change_seat(Request $request, $code)
    {

        /* Validation */
        $validator = Validator::make($request->all(), [
            'passenger' => 'required|integer',
            'seat' => 'required|regex:/\d+[A-Z]+/i',
            'type' => 'required',
        ]);

        /* If validation failed*/
        if($validator->fails()) {

            return response([
                'error' => [
                    'code' => 403,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422)->header('Content-Type', 'application/json');

        }

        $booking = Booking::where('code', $code)->first();
        $passenger = Passenger::find($request->passenger)->select('id', 'first_name', 'last_name', 'last_name', 'birth_date', 'document_number', 'place_from', 'place_back')->first();

        /* Is seat occupied? */
        if($booking->passengers->where("place_$request->type", $request->seat)->count()) {

            return response([
                'error' => [
                    'code' => 422,
                    'message' => 'seat is occupied'
                ]
            ], 422)->header('Content-Type', 'application/json');

        } else {

            $passenger->update([
                "place_$request->type" => $request->seat
            ]);

            return response([ 'data' => $passenger ], 200)->header('Content-Type', 'application/json');

        }


    }

}
