<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CalculationController;
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




Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('calculate/emi-loan', [CalculationController::class, 'calculateEmiLoan']);
    Route::post('calculate/future-value', [CalculationController::class, 'calculateFutureValue']);
    Route::post('saved-calculations', [CalculationController::class, 'saveCalculation']);
    Route::get('saved-calculations', [CalculationController::class, 'listSavedCalculations']);
    Route::delete('saved-calculations/{id}', [CalculationController::class, 'deleteSavedCalculation']);
});
