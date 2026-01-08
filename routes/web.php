<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function(){
    return Inertia::render('Welcome');
})->name('home');


Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Offline Time Tracking Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('offline-time/report', [\App\Http\Controllers\OfflineTimeEntryController::class, 'report'])
        ->name('offline-time.report');

    Route::resource('offline-time', \App\Http\Controllers\OfflineTimeEntryController::class)
        ->parameters(['offline-time' => 'offline_time_entry']);
    
    Route::get('offline-time-summary', [\App\Http\Controllers\OfflineTimeEntryController::class, 'monthlySummary'])
        ->name('offline-time.summary');
});



require __DIR__ . '/settings.php';
