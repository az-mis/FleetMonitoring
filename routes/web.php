<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GssImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

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
    // Import an updated GSS workbook (Super Admin only): check first, then import
    Route::post('/reports/gss-import', [GssImportController::class, 'check'])->name('reports.gss-import.check');
    Route::get('/reports/gss-import', [GssImportController::class, 'show'])->name('reports.gss-import.show');
    Route::post('/reports/gss-import/commit', [GssImportController::class, 'commit'])->name('reports.gss-import.commit');
    Route::get('/reports/gss-import/review/{file}', [GssImportController::class, 'review'])->name('reports.gss-import.review');
    Route::get('/my-profile', [ProfileController::class, 'edit'])->name('my-profile');
    Route::put('/my-profile', [ProfileController::class, 'update'])->name('my-profile.update');
    Route::view('/settings', 'settings')->name('settings');
});
