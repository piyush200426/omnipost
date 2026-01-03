<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded via RouteServiceProvider and will be assigned
| to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


/*
|--------------------------------------------------------------------------
| LIVE ENGAGEMENT DATA API
|--------------------------------------------------------------------------
| This endpoint returns random engagement data for testing live charts.
| Later we will replace this with real Facebook/Instagram insights.
*/
Route::get('/engagement-live', function () {

    return response()->json([
        "labels" => ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],

        // Auto-refresh random data for testing live updates
        "data"   => [
            rand(100, 300),
            rand(300, 600),
            rand(500, 900),
            rand(800, 1500),
            rand(600, 1200),
            rand(400, 900),
            rand(300, 700)
        ]
    ]);
});
