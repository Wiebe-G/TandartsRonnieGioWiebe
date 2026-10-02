<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RouteController;
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
});

require __DIR__.'/settings.php';
