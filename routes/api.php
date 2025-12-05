<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DonationController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Rate Limiters Configuration
|--------------------------------------------------------------------------
| Define custom rate limiters for different endpoints
*/

// Rate limiter for donation initialization (stricter)
RateLimiter::for('donations-initialize', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->ip())
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Too many donation attempts. Please try again in a minute.',
            ], 429, $headers);
        });
});

// Rate limiter for donation bank transfer
RateLimiter::for('donations-bank-transfer', function (Request $request) {
    return Limit::perMinute(30)
        ->by($request->ip())
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Too many donation attempts. Please try again in a minute.',
            ], 429, $headers);
        });
});

// Rate limiter for status checks (more lenient for polling)
RateLimiter::for('donations-status', function (Request $request) {
    return Limit::perMinute(30)
        ->by($request->ip() . '|' . $request->route('reference'))
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Too many status check requests. Please wait.',
            ], 429, $headers);
        });
});

// Rate limiter for webhook (high limit, but still protected)
RateLimiter::for('donations-webhook', function (Request $request) {
    return Limit::perMinute(100)
        ->by($request->ip())
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'message' => 'Rate limit exceeded',
            ], 429, $headers);
        });
});


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('donations/initialize', [DonationController::class, 'initialize'])
    ->middleware('throttle:donations-initialize');

Route::get('donations/verify/{reference}', [DonationController::class, 'checkStatus'])
    ->middleware('throttle:donations-status');

Route::post('webhook/paystack', [DonationController::class, 'webhook'])
    ->middleware('throttle:donations-webhook');

Route::post('donations/bank-transfer', [DonationController::class, 'bankTransfer'])
    ->middleware('throttle:donations-bank-transfer');
