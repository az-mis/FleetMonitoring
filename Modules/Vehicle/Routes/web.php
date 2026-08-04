<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Controllers\VehicleController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('vehicles')
    ->name('vehicles.')
    ->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::post('/', [VehicleController::class, 'store'])->name('store');
    });
