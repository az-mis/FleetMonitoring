<?php

use Illuminate\Support\Facades\Route;
use Modules\Destination\Controllers\DestinationController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
    Route::post('/destinations', [DestinationController::class, 'store'])->name('destinations.store');
    Route::put('/destinations/{destination}', [DestinationController::class, 'update'])->name('destinations.update');
    Route::patch('/destinations/{destination}/toggle', [DestinationController::class, 'toggle'])->name('destinations.toggle');
    Route::delete('/destinations/{destination}', [DestinationController::class, 'destroy'])->name('destinations.destroy');
});

// Public / API endpoint for fetching destinations by location
Route::get('/api/destinations-by-location', [DestinationController::class, 'listByLocation'])->name('destinations.by-location');
