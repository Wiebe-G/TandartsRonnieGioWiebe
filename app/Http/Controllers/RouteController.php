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
        return Inertia::render('Appointments', [
            'Appointments' => Appointment::all(),
        ]);
    }

    public function treatments()
    {
        return Inertia::render('Treatments', [
            'Treatments' => Treatment::all(),
        ]);
    }
}
