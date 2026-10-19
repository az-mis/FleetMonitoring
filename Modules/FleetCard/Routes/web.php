<?php

use Illuminate\Support\Facades\Route;
use Modules\FleetCard\Controllers\FleetCardController;
use Modules\FleetCard\Controllers\FleetCardSpendController;

Route::middleware(['web', 'auth', 'verified'])->prefix('fleet-cards')->name('fleet-cards.')->group(function () {
    Route::get('/', [FleetCardController::class, 'index'])->name('index');
    Route::post('/', [FleetCardController::class, 'store'])->name('store');
    // Monthly spend per division (GSS "Index for Fleetcard"); registered before the /{fleetCard} routes
    Route::get('/monthly-spend', [FleetCardSpendController::class, 'index'])->name('monthly-spend');
    Route::post('/monthly-spend', [FleetCardSpendController::class, 'store'])->name('monthly-spend.store');
    Route::delete('/monthly-spend/{spend}', [FleetCardSpendController::class, 'destroy'])->name('monthly-spend.destroy');
    Route::put('/{fleetCard}', [FleetCardController::class, 'update'])->name('update');
    Route::delete('/{fleetCard}', [FleetCardController::class, 'destroy'])->name('destroy');
    Route::post('/{fleetCard}/log-expense', [FleetCardController::class, 'logExpense'])->name('log-expense');
    Route::post('/{fleetCard}/recharge', [FleetCardController::class, 'recharge'])->name('recharge');
    Route::delete('/{fleetCard}/recharge/{log}', [FleetCardController::class, 'destroyRecharge'])->name('destroy-recharge');
    Route::get('/{fleetCard}/preview-calc', [FleetCardController::class, 'previewCalc'])->name('preview-calc');
    Route::delete('/{fleetCard}/log/{log}', [FleetCardController::class, 'destroyLog'])->name('destroy-log');
    Route::put('/{fleetCard}/log/{log}', [FleetCardController::class, 'updateLog'])->name('update-log');
});
