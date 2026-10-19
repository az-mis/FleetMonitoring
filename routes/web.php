<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('public.landing');
});

Route::view('/landing', 'public.landing')->name('landing');
Route::view('/unauthorized', 'public.unauthorized')->name('unauthorized');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/export/{format}', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/gss-workbook', [ReportController::class, 'gssWorkbook'])->name('reports.gss-workbook');
    Route::get('/my-profile', [ProfileController::class, 'edit'])->name('my-profile');
    Route::put('/my-profile', [ProfileController::class, 'update'])->name('my-profile.update');
    Route::view('/settings', 'settings')->name('settings');
});
