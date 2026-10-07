<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Treatment;
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
}
