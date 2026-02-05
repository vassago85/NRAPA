<?php

use App\Http\Controllers\Api\FirearmReferenceController;
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

Route::middleware(['throttle:api'])->group(function () {
    // Calibre endpoints
    Route::get('/calibres/suggest', [FirearmReferenceController::class, 'suggestCalibres']);
    Route::get('/calibres/resolve', [FirearmReferenceController::class, 'resolveCalibre']);
    Route::get('/calibres/{id}', [FirearmReferenceController::class, 'getCalibre']);

    // Make endpoints
    Route::get('/makes/suggest', [FirearmReferenceController::class, 'suggestMakes']);
    Route::get('/makes/{id}', [FirearmReferenceController::class, 'getMake']);
    Route::get('/makes/{id}/models', [FirearmReferenceController::class, 'getMakeModels']);
});
