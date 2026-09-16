<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\ContentSettingsController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/content-settings', [ContentSettingsController::class, 'edit'])->name('content-settings');
    Route::put('/content-settings', [ContentSettingsController::class, 'update'])->name('content-settings.update');
});
