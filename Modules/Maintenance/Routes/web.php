<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Controllers\MaintenanceController;
use Modules\Maintenance\Controllers\RepairRecordController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('maintenance')
    ->name('maintenance.')
    ->group(function () {
        Route::get('/',                                [MaintenanceController::class, 'index'])->name('index');
        Route::post('/',                               [MaintenanceController::class, 'store'])->name('store');
        Route::put('/{maintenance}',                   [MaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenance}',                [MaintenanceController::class, 'destroy'])->name('destroy');

        // Repair details and purchase requests (GSS workbook)
        Route::get('/{maintenance}/repair',            [RepairRecordController::class, 'show'])->name('repair');
        Route::put('/{maintenance}/repair',            [RepairRecordController::class, 'update'])->name('repair.update');
        Route::post('/{maintenance}/procurements',     [RepairRecordController::class, 'storeProcurement'])->name('procurements.store');
        Route::put('/{maintenance}/procurements/{procurement}',    [RepairRecordController::class, 'updateProcurement'])->name('procurements.update');
        Route::delete('/{maintenance}/procurements/{procurement}', [RepairRecordController::class, 'destroyProcurement'])->name('procurements.destroy');
    });
