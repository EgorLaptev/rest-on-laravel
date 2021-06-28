<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function() {

    /* User */
    Route::post('/register', 'App\Http\Controllers\UserController@register')->name('register');
    Route::post('/login', 'App\Http\Controllers\UserController@login')->name('login');
    Route::get('/user', 'App\Http\Controllers\UserController@show')->name('user.show');
    Route::get('/user/booking', 'App\Http\Controllers\UserController@bookings')->name('user.bookings');

    /* Airport */
    Route::get('/airport', 'App\Http\Controllers\AirportController@show')->name('airport.show');

    /* Flight */
    Route::get('/flight', 'App\Http\Controllers\FlightController@show')->name('flight.show');

    /* Booking */
    Route::post('/booking', 'App\Http\Controllers\BookingController@store')->name('booking.store');
    Route::get('/booking/{booking_code}', 'App\Http\Controllers\BookingController@show')->name('booking.show');
    Route::get('/booking/{booking_code}/seat', 'App\Http\Controllers\BookingController@seat')->name('booking.seat');
    Route::patch('/booking/{booking_code}/seat', 'App\Http\Controllers\BookingController@change_seat')->name('booking.change_seat');

});
