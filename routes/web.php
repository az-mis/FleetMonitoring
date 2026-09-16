<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('public.landing');
});

Route::view('/landing', 'public.landing')->name('landing');
Route::view('/unauthorized', 'public.unauthorized')->name('unauthorized');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/my-profile', [ProfileController::class, 'edit'])->name('my-profile');
    Route::put('/my-profile', [ProfileController::class, 'update'])->name('my-profile.update');
    Route::view('/settings', 'settings')->name('settings');
});
