<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Controllers\AccountActivationController;

Route::middleware(['web', 'guest'])->group(function () {
    // Reserved for custom auth-adjacent routes later
});

// The link in the activation email (see AccountActivation). Not guest-only: an admin may open it on a shared office
// computer where someone is still signed in; activating signs that browser in as the driver.
Route::middleware('web')->group(function () {
    Route::get('account/activate/{user}', [AccountActivationController::class, 'show'])->name('account.activate');
    Route::post('account/activate/{user}', [AccountActivationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('account.activate.store');
});
