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
        $user = auth()->user();

        return Inertia::render('dashboard', [
            // Eigen afspraken die nog moeten komen (als klant, tandarts of assistent), niet geannuleerd
            'Appointments' => Appointment::query()
                ->where(fn ($query) => $query
                    ->where('customer_id', $user->id)
                    ->orWhere('dentist_id', $user->id)
                    ->orWhere('assistant_id', $user->id))
                ->where(fn ($query) => $query
                    ->whereDate('date', '>', today())
                    ->orWhere(fn ($query) => $query->whereDate('date', today())->where('endtime', '>', now()->format('H:i:s'))))
                ->where('status', '!=', 'cancelled')
                ->with(['customer', 'treatments'])
                ->orderBy('date')
                ->orderBy('starttime')
                ->get()
                ->values(),
            // Praktijkstatistieken, alleen voor beheerders
            'Statistics' => auth()->user()->isAdmin() ? (new PracticeStatistics)->toArray() : null,
        ]);
    }
}
