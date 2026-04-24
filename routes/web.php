<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityUpdateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('activities', ActivityController::class)->except(['show']);
    Route::post('activity-updates', [ActivityUpdateController::class, 'store'])->name('activity_updates.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
