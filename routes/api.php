<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SwaggerController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\EmissionController;

Route::get('/', function () {
    return response()->json([
        'message' => 'GreenTrip API',
        'version' => '1.0.0',
    ]);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Email Verification Routes
Route::get('verify-email/{token}', [EmailVerificationController::class, 'verify']);
Route::post('resend-verification', [EmailVerificationController::class, 'resend']);

Route::group(['prefix' => 'users', 'middleware' => 'auth:api'], function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('{id}', [UserController::class, 'show']);
    Route::put('{id}', [UserController::class, 'update']);
    Route::delete('{id}', [UserController::class, 'destroy']);
});

// Flight Routes
Route::group(['prefix' => 'flights', 'middleware' => 'auth:api'], function () {
    Route::post('search', [FlightController::class, 'search']);
    Route::post('book', [FlightController::class, 'book']);
    Route::get('airports', [FlightController::class, 'airports']);
    Route::get('{flightId}', [FlightController::class, 'show']);
});

// Booking Routes
Route::group(['prefix' => 'bookings', 'middleware' => 'auth:api'], function () {
    Route::get('/', [FlightController::class, 'userBookings']);
    Route::get('{bookingId}', [FlightController::class, 'showBooking']);
    Route::delete('{bookingId}', [FlightController::class, 'cancelBooking']);
});

// Emission Routes
Route::group(['prefix' => 'emissions', 'middleware' => 'auth:api'], function () {
    Route::post('calculate', [EmissionController::class, 'calculate']);
});

// Swagger Documentation Routes
Route::group(['prefix' => 'swagger'], function () {
    Route::get('/', [SwaggerController::class, 'index']);
    Route::get('spec', [SwaggerController::class, 'spec']);
});