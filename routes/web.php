<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\TreatmentHistoryController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    // Local preview only: open the customer dashboard as the seeded demo customer without logging in.
    Route::get('/', function () {
        Auth::login(User::where('role_id', Role::customerId())->orderBy('id')->firstOrFail());

        return redirect()->route('dashboard');
    })->name('home');
} else {
    Route::inertia('/', 'welcome')->name('home');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('afspraken', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('afspraken/nieuw', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('afspraken', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('afspraken/{appointment}/annuleren', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('afspraken/{appointment}/feedback', [FeedbackController::class, 'store'])->name('appointments.feedback');

    Route::get('behandelingen', TreatmentHistoryController::class)->name('treatments.history');
});

require __DIR__.'/settings.php';
