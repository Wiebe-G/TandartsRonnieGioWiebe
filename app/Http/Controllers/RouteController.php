<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
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
}
