<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Driver\Controllers\DriverController;

Route::middleware(['web', 'auth', 'verified', 'module:drivers'])
    ->prefix('driver')
    ->name('driver.')
    ->group(function () {
        // Deactivate instead of delete, so a driver's trips and reports keep their name (decision D10)
        Route::patch('{driver}/deactivate', [DriverController::class, 'deactivate'])->name('deactivate');
        Route::patch('{driver}/reactivate', [DriverController::class, 'reactivate'])->name('reactivate');

        Route::resource('/', DriverController::class)
            ->parameters(['' => 'driver'])
            ->except(['show']);
    });
