<?php

use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\TreatmentController as AdminTreatmentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\TreatmentHistoryController;
use Illuminate\Support\Facades\Route;

// Always the welcome page; nobody is logged in automatically.
// To look around as a customer, an admin uses "Bekijk als klant" on the customer's page.
Route::inertia('/', 'welcome')->name('home');

// Back to the admin after "Bekijk als klant" (checked in the controller)
Route::post('bekijk-als-klant/stoppen', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonation.stop');

Route::middleware(['auth', 'verified'])->group(function () {
    // Customers get their own start screen, everyone else the regular dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Customers book, view and cancel their appointments here
    Route::get('afspraken', [BookingController::class, 'index'])->name('appointments.index');
    Route::get('afspraken/nieuw', [BookingController::class, 'create'])->name('appointments.create');
    Route::post('afspraken', [BookingController::class, 'store'])->name('appointments.store');
    Route::patch('afspraken/{appointment}/annuleren', [BookingController::class, 'cancel'])->name('appointments.cancel');
    Route::post('afspraken/{appointment}/feedback', [FeedbackController::class, 'store'])->name('appointments.feedback');

    Route::get('behandelingen', TreatmentHistoryController::class)->name('treatments.history');
});

Route::middleware(['auth', 'Employee'])->group(function () {
    Route::get('/appointments', [RouteController::class, 'appointments'])
        ->name('appointments');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])
        ->name('appointments.staff.create');
    Route::post('/appointments/create', [AppointmentController::class, 'store'])
        ->name('appointments.staff.store');
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
    Route::post('/customers/{customer}/bekijk-als-klant', [ImpersonationController::class, 'start'])->name('customers.impersonate');
    Route::resource('employees', EmployeeController::class);
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::put('/calendar/appointments/{appointment}', [CalendarController::class, 'update'])->name('calendar.update');

    // Admin versions of the appointment and treatment pages
    Route::resource('appointments', AdminAppointmentController::class)->except('edit');
    Route::put('/appointments/{appointment}/room', [AdminAppointmentController::class, 'updateRoom'])->name('appointments.room');
    Route::resource('treatments', AdminTreatmentController::class)->except(['show', 'destroy']);
});

require __DIR__.'/settings.php';
