<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ApprovingOfficer\Controllers\ApprovingOfficerController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('approving-officers')
    ->name('approving-officers.')
    ->group(function () {
        Route::get('/', [ApprovingOfficerController::class, 'index'])->name('index');
        Route::post('/', [ApprovingOfficerController::class, 'store'])->name('store');
        Route::patch('/{approvingOfficer}/ticket-signatories', [ApprovingOfficerController::class, 'updateTicketSelection'])->name('ticket-selection');
        Route::delete('/{approvingOfficer}', [ApprovingOfficerController::class, 'destroy'])->name('destroy');
    });
