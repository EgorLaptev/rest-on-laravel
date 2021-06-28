<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|unique:users',
            'document_number' => 'required|digits:10',
            'password' => 'required|string',
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

        $user = User::create([
            'first_name' => $validator->validated()['first_name'],
            'last_name' => $validator->validated()['last_name'],
            'phone' => $validator->validated()['phone'],
            'document_number' => $validator->validated()['document_number'],
            'password' => Hash::make($validator->validated()['password']),
            'api_token' => bin2hex(random_bytes(15)),
        ]);

        return response(null, 204);

    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {

            return response([
                'errors' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ]);

        }

        if (Auth::attempt([
            'phone' => $request->phone,
            'password' => $request->password
        ])) {

            return response([
                'data' => [
                    'token' => User::where('phone', $request->phone)->first()['api_token']
                ]
            ], 200);

        } else {

            return response([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorizated',
                    'errors' => [
                        'phone' => ['phone or password incorrect']
                    ]
                ]
            ], 401);

        }

    }

    public function bookings(Request $request)
    {

        $user = User::where('api_token', $request->bearerToken())->first();

        if (!empty($user)) {

            $items = [];

            foreach ($user->bookings() as $booking) {

                $flights = [];

                foreach ($booking->flights() as $flight) {

                    $availability = 165;

                    foreach ($flight->bookings() as $booking_) {
                        $availability -= $booking_->passengers->count();
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

                $passengers = [];

                foreach ($booking->passengers  as $passenger) {

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

                $items[] = [
                    'code' => $booking['code'],
                    'cost' => $booking->flights()[0]['cost'] + $booking->flights()[1]['cost'],
                    'flights' => $flights,
                    'passengers' => $passengers
                ];

            }

            return response([
                'data' => [
                    'items' => $items
                ]
            ]);

        } else return response([
            'error' => [
                'code' => 401,
                'message' => 'Unauthorized'
            ]
        ]);

    }

    public function show(Request $request)
    {

        $user = User::select('first_name', 'last_name', 'phone', 'document_number')
            ->where('api_token', $request->bearerToken())
            ->first();

        if (!empty($user))
            return response($user, 200)->header('Content-Type', 'application/json');
        else
            return response([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorized'
                ]
            ], 401)->header('Content-Type', 'application/json');
    }

}
