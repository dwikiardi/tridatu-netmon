<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/waha/webhook', [\App\Http\Controllers\WahaController::class, 'webhook']);

// Asset Management API
Route::middleware('internal.api')->group(function () {
    Route::get('/v1/customers', [\App\Http\Controllers\Api\AssetApiController::class, 'getCustomers']);
    Route::get('/v1/staff', [\App\Http\Controllers\Api\AssetApiController::class, 'getStaff']);
});
