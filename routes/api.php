<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Auto-refresh endpoints
    Route::get('/attendance-logs', [\App\Http\Controllers\Api\AttendanceController::class, 'getLogs']);
    Route::get('/employees', [\App\Http\Controllers\Api\EmployeeController::class, 'getList']);
    Route::get('/dtr-reports', [\App\Http\Controllers\Api\DTRController::class, 'getReports']);
});
