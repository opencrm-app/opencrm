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

    Route::resource('teams', \App\Http\Controllers\TeamController::class);
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::get('projects/domains', [\App\Http\Controllers\ProjectController::class, 'domainList'])->name('projects.domains');
    Route::resource('projects', \App\Http\Controllers\ProjectController::class);
    Route::resource('reels', \App\Http\Controllers\ReelController::class);
    Route::get('add', [\App\Http\Controllers\ReelController::class, 'create'])->name('reels.add');
    Route::post('teams/{team}/members', [\App\Http\Controllers\TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::put('teams/{team}/members/{member}', [\App\Http\Controllers\TeamMemberController::class, 'update'])->name('teams.members.update');
    Route::delete('teams/{team}/members/{member}', [\App\Http\Controllers\TeamMemberController::class, 'destroy'])->name('teams.members.destroy');

    // ScreenshotMonitor API
    Route::get('api/ssm/daily-stats', [\App\Http\Controllers\Api\ScreenshotMonitorController::class, 'getDailyStats'])->name('ssm.daily-stats');
});

require __DIR__ . '/settings.php';
