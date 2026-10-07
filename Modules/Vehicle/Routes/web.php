<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Controllers\VehicleController;
use Modules\Vehicle\Controllers\VehicleAssigningController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('vehicles')
    ->name('vehicles.')
    ->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::post('/', [VehicleController::class, 'store'])->name('store');
        Route::post('/types', [VehicleController::class, 'storeType'])->name('types.store');
        Route::post('/{vehicle}/inspections', [VehicleController::class, 'storeInspection'])->name('inspections.store');
        Route::get('/{vehicle}/inspections', [VehicleController::class, 'indexInspections'])->name('inspections.index');
        Route::put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('vehicle-assigning')
    ->name('vehicle-assigning.')
    ->group(function () {
        Route::get('/', [VehicleAssigningController::class, 'index'])->name('index');
        Route::patch('/{vehicle}', [VehicleAssigningController::class, 'update'])->name('update');
    });
