<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Controllers\MaintenanceController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('maintenance')
    ->name('maintenance.')
    ->group(function () {
        Route::get('/',                                [MaintenanceController::class, 'index'])->name('index');
        Route::post('/',                               [MaintenanceController::class, 'store'])->name('store');
        Route::put('/{maintenance}',                   [MaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenance}',                [MaintenanceController::class, 'destroy'])->name('destroy');
    });
