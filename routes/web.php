<?php

use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\TreatmentController as AdminTreatmentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [RouteController::class, 'dashboard'])
        ->name('dashboard');
});

Route::middleware(['auth', 'Employee'])->group(function () {
    Route::get('/appointments', [RouteController::class, 'appointments'])
        ->name('appointments');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])
        ->name('appointments.create');
    Route::post('/appointments/create', [AppointmentController::class, 'store'])
        ->name('appointments.store');
    Route::get('/appointments/view/{appointment}', [AppointmentController::class, 'show'])
        ->name('appointments.view');
    Route::post('/appointments/update', [AppointmentController::class, 'update'])
        ->name('appointments.update');
    Route::post('/appointments/delete', [AppointmentController::class, 'destroy'])
        ->name('appointments.delete');

    Route::get('/treatments', [RouteController::class, 'treatments'])
        ->name('treatments');
    Route::get('/treatments/create', [TreatmentController::class, 'create'])
        ->name('treatments.create');
    Route::post('/treatments/create', [TreatmentController::class, 'store'])
        ->name('treatments.store');
    Route::get('/treatments/{treatment}/edit', [TreatmentController::class, 'edit'])
        ->name('treatments.edit');
    Route::put('/treatments/{treatment}', [TreatmentController::class, 'update'])
        ->name('treatments.update');
});

Route::middleware(['auth', 'Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/users', UserManagementController::class)->name('users');
    Route::resource('customers', CustomerController::class);
    Route::resource('employees', EmployeeController::class);
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::put('/calendar/appointments/{appointment}', [CalendarController::class, 'update'])->name('calendar.update');

    // Admin versions of the appointment and treatment pages
    Route::resource('appointments', AdminAppointmentController::class)->except('edit');
    Route::put('/appointments/{appointment}/room', [AdminAppointmentController::class, 'updateRoom'])->name('appointments.room');
    Route::resource('treatments', AdminTreatmentController::class)->except(['show', 'destroy']);
});

require __DIR__.'/settings.php';
