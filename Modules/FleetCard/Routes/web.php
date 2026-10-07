<?php

use Illuminate\Support\Facades\Route;
use Modules\FleetCard\Controllers\FleetCardController;

Route::middleware(['web', 'auth', 'verified'])->prefix('fleet-cards')->name('fleet-cards.')->group(function () {
    Route::get('/', [FleetCardController::class, 'index'])->name('index');
    Route::post('/', [FleetCardController::class, 'store'])->name('store');
    Route::put('/{fleetCard}', [FleetCardController::class, 'update'])->name('update');
    Route::delete('/{fleetCard}', [FleetCardController::class, 'destroy'])->name('destroy');
    Route::post('/{fleetCard}/log-expense', [FleetCardController::class, 'logExpense'])->name('log-expense');
    Route::post('/{fleetCard}/recharge', [FleetCardController::class, 'recharge'])->name('recharge');
    Route::get('/{fleetCard}/preview-calc', [FleetCardController::class, 'previewCalc'])->name('preview-calc');
});
