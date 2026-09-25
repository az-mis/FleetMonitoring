<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleReport\Controllers\VehicleReportController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('vehicle-reports')
    ->name('vehicle-reports.')
    ->group(function () {
        Route::get('/',                            [VehicleReportController::class, 'index'])->name('index');
        Route::post('/',                           [VehicleReportController::class, 'store'])->name('store');
        Route::put('/{vehicleReport}',              [VehicleReportController::class, 'review'])->name('review');
        Route::post('/{vehicleReport}/maintenance', [VehicleReportController::class, 'createMaintenance'])->name('create-maintenance');
    });
