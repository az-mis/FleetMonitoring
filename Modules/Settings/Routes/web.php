<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\ContentSettingsController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/content-settings', [ContentSettingsController::class, 'edit'])->name('content-settings');
    Route::put('/content-settings', [ContentSettingsController::class, 'update'])->name('content-settings.update');
    Route::post('/content-settings/toggle', [ContentSettingsController::class, 'updateToggle'])->name('content-settings.toggle');
    Route::post('/content-settings/announcement/{target}', [ContentSettingsController::class, 'updateAnnouncement'])->name('content-settings.announcement.update');
    Route::post('/content-settings/announcement/{target}/toggle', [ContentSettingsController::class, 'toggleAnnouncement'])->name('content-settings.announcement.toggle');
    Route::post('/content-settings/logo', [ContentSettingsController::class, 'updateLogo'])->name('content-settings.logo.update');
    Route::delete('/content-settings/logo', [ContentSettingsController::class, 'removeLogo'])->name('content-settings.logo.destroy');
});
