<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Support\PracticeStatistics;
use Inertia\Inertia;

class RouteController extends Controller
{
    //
    public function appointments()
    {
        $Appointments = Appointment::with(['customer', 'treatments'])->get()->values();

        return Inertia::render('Appointments', [
            'Appointments' => $Appointments,
        ]);
    }

    public function treatments()
    {
        return Inertia::render('Treatments', [
            'Treatments' => Treatment::all(),
        ]);
    }

    public function dashboard()
    {
        return Inertia::render('dashboard', [
            // Alle afspraken van vandaag of in de toekomst
            'Appointments' => Appointment::orderBy('date')->with(['customer', 'treatments'])->get()->where('date', '>=', date('Y-m-d'))->values(),
            // Praktijkstatistieken, alleen voor beheerders
            'Statistics' => auth()->user()->isAdmin() ? (new PracticeStatistics)->toArray() : null,
        ]);
    }
}
