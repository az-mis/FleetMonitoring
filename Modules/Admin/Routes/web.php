<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Controllers\AdminController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('/', AdminController::class)
            ->parameters(['' => 'admin'])
            ->except(['show']);
    });