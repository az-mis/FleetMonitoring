<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRequest\Controllers\VehicleRequestController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/request-vehicle', [VehicleRequestController::class, 'create'])->name('request-vehicle');
    Route::post('/request-vehicle', [VehicleRequestController::class, 'store'])->name('request-vehicle.store');
    Route::get('/check-status', [VehicleRequestController::class, 'status'])->name('check-status');
    Route::get('/trip-ticket/{vehicleRequest}', [VehicleRequestController::class, 'tripTicket'])->name('trip-ticket');
});
Route::middleware(['web', 'auth', 'verified'])->get('/travel-history', [VehicleRequestController::class, 'travelHistory'])->name('travel-history');

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/vehicle-requests', [VehicleRequestController::class, 'index'])->name('vehicle-requests.index');
    Route::patch('/vehicle-requests/{vehicleRequest}/status', [VehicleRequestController::class, 'updateStatus'])->name('vehicle-requests.status');
});
