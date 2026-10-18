<?php

use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\TreatmentController as AdminTreatmentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\TreatmentHistoryController;
use App\Support\OpeningHours;
use Illuminate\Support\Facades\Route;

// Always the welcome page; nobody is logged in automatically.
// To look around as a customer, an admin uses "Bekijk als klant" on the customer's page.
Route::inertia('/', 'welcome', [
    // Same hours the booking uses, so the homepage can't show different times
    'openingHours' => [
        'open' => substr(OpeningHours::OPEN, 0, 5),
        'close' => substr(OpeningHours::CLOSE, 0, 5),
    ],
])->name('home');

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

    // The customer's own invoices (the ones that were sent to them)
    Route::get('facturen', [CustomerInvoiceController::class, 'index'])->name('customer.invoices.index');
    Route::get('facturen/{invoice}', [CustomerInvoiceController::class, 'show'])->name('customer.invoices.show');
    Route::post('facturen/{invoice}/betalen', [CustomerInvoiceController::class, 'pay'])->name('customer.invoices.pay');
});

Route::middleware(['auth', 'Employee'])->group(function () {
    Route::get('/appointments', [RouteController::class, 'appointments'])
        ->name('appointments');
    // Making appointments: assistants and admins only
    Route::get('/appointments/create', [AppointmentController::class, 'create'])
        ->middleware('AssistantOrAdmin')
        ->name('appointments.staff.create');
    Route::post('/appointments/create', [AppointmentController::class, 'store'])
        ->middleware('AssistantOrAdmin')
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
});

// Making appointments and sending invoices: assistants and admins only
// (before the group below, so /appointments/create is not taken for an appointment id)
Route::middleware(['auth', 'AssistantOrAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('appointments', AdminAppointmentController::class)->only(['create', 'store']);

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/appointments/{appointment}/invoice', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/versturen', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::patch('/invoices/{invoice}/betaling', [InvoiceController::class, 'updatePayment'])->name('invoices.payment');
});

// The appointment and treatment pages, shared by admins and the other staff (dentists, assistants, ...)
Route::middleware(['auth', 'Employee'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('appointments', AdminAppointmentController::class)->except(['create', 'store', 'edit']);
    Route::put('/appointments/{appointment}/room', [AdminAppointmentController::class, 'updateRoom'])->name('appointments.room');
    Route::patch('/appointments/{appointment}/afronden', [AdminAppointmentController::class, 'complete'])->name('appointments.complete');
    Route::resource('treatments', AdminTreatmentController::class)->except(['show', 'destroy']);
});

require __DIR__.'/settings.php';
