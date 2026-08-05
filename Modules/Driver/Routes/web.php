<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Driver\Controllers\DriverController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('driver')
    ->name('driver.')
    ->group(function () {
        Route::resource('/', DriverController::class)
            ->parameters(['' => 'driver'])
            ->except(['show']);
    });
