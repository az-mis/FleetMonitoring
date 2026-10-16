<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRequest\Controllers\VehicleRequestController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/request-vehicle', [VehicleRequestController::class, 'create'])->name('request-vehicle');
    // Public, unauthenticated submission: rate-limited to curb spam bookings
    Route::post('/request-vehicle', [VehicleRequestController::class, 'store'])->middleware('throttle:10,1')->name('request-vehicle.store');
    Route::get('/request-vehicle/station-availability', [VehicleRequestController::class, 'stationAvailability'])->middleware('throttle:60,1')->name('request-vehicle.station-availability');
    Route::get('/request-vehicle/{vehicle}/availability', [VehicleRequestController::class, 'availability'])->middleware('throttle:60,1')->name('request-vehicle.availability');
    Route::get('/check-status', [VehicleRequestController::class, 'status'])->middleware('throttle:30,1')->name('check-status');
    Route::get('/trip-ticket/{vehicleRequest}', [VehicleRequestController::class, 'tripTicket'])->middleware('throttle:30,1')->name('trip-ticket');
});
Route::middleware(['web', 'auth', 'verified'])->get('/travel-history', [VehicleRequestController::class, 'travelHistory'])->name('travel-history');

Route::middleware(['web', 'auth', 'verified', 'module:vehicleRequests'])->group(function () {
    Route::get('/vehicle-requests', [VehicleRequestController::class, 'index'])->name('vehicle-requests.index');
    Route::patch('/vehicle-requests/{vehicleRequest}/status', [VehicleRequestController::class, 'updateStatus'])->name('vehicle-requests.status');
    Route::post('/vehicle-requests/admin-create', [VehicleRequestController::class, 'adminStore'])->name('vehicle-requests.admin-create');
    Route::get('/vehicle-requests/lookup-trip', [VehicleRequestController::class, 'lookupTripByTicketNumber'])->name('vehicle-requests.lookup-trip');
    Route::post('/vehicle-requests/{vehicleRequest}/complete-trip', [VehicleRequestController::class, 'storeTripCompletion'])->name('vehicle-requests.complete-trip');
});
