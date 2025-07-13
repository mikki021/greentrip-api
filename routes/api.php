<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SwaggerController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\EmissionController;
use App\Http\Controllers\EmissionsReportingController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json([
        'message' => 'GreenTrip API',
        'version' => '1.0.0',
    ]);
});

// Auth routes with strict rate limiting
Route::group(['prefix' => 'auth', 'middleware' => 'throttle:auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Email Verification Routes with moderate rate limiting
Route::group(['middleware' => 'throttle:general'], function () {
    Route::get('verify-email/{token}', [EmailVerificationController::class, 'verify']);
    Route::post('resend-verification', [EmailVerificationController::class, 'resend']);
});

// User management with general rate limiting
Route::group(['prefix' => 'users', 'middleware' => ['auth:api', 'throttle:general']], function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('{id}', [UserController::class, 'show']);
    Route::put('{id}', [UserController::class, 'update']);
    Route::delete('{id}', [UserController::class, 'destroy']);
});

// Flight search with specific rate limiting
Route::group(['prefix' => 'flights', 'middleware' => ['auth:api', 'throttle:search']], function () {
    Route::post('search', [FlightController::class, 'search']);
    Route::post('book', [FlightController::class, 'book']);
    Route::get('airports', [FlightController::class, 'airports']);
    Route::get('{flightId}', [FlightController::class, 'show']);
});

// Booking management with general rate limiting
Route::group(['prefix' => 'bookings', 'middleware' => ['auth:api', 'throttle:general']], function () {
    Route::get('/', [FlightController::class, 'userBookings']);
    Route::get('{bookingId}', [FlightController::class, 'showBooking']);
    Route::delete('{bookingId}', [FlightController::class, 'cancelBooking']);
});

// Emissions with higher rate limiting (cached responses)
Route::group(['prefix' => 'emissions', 'middleware' => ['auth:api', 'throttle:emissions']], function () {
    Route::post('calculate', [EmissionController::class, 'calculate']);
    Route::get('summary', [EmissionsReportingController::class, 'getEmissionsSummary']);
    Route::delete('cache', [EmissionsReportingController::class, 'clearCache']);
});

// Swagger Documentation Routes with minimal rate limiting
Route::group(['prefix' => 'swagger', 'middleware' => 'throttle:general'], function () {
    Route::get('/', [SwaggerController::class, 'index']);
    Route::get('spec', [SwaggerController::class, 'spec']);
});