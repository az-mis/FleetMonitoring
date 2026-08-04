<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest'])->group(function () {
    // Reserved for custom auth-adjacent routes later
});