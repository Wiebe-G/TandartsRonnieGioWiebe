<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'Employee'])->group(function () {
    Route::get('/appointments', [RouteController::class, 'appointments'])
        ->name('appointments');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])
        ->name('appointments.create');
    Route::post('/appointments/create', [AppointmentController::class, 'store'])
        ->name('appointments.store');

    Route::get('/treatments', [RouteController::class, 'treatments'])
        ->name('treatments');
    Route::get('/treatments/create', [TreatmentController::class, 'create'])
        ->name('treatments.create');
    Route::post('/treatments/create', [TreatmentController::class, 'store'])
        ->name('treatments.store');
});

require __DIR__.'/settings.php';
