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

    public function dashboard()
    {
        $CustomerAppointments = Appointment::query()->where(
			['customer_id', '=', auth()->user()->id],
			['date', '>=' , date('Y-m-d')]
		);
        $DentistAppointments = Appointment::query()->where('dentist_id', '=', auth()->user()->id);
        $AssistantAppointments = Appointment::query()->where('assistant_id', '=', auth()->user()->id);

        return Inertia::render('dashboard', [
            'CustomerAppointments' => $CustomerAppointments,
            'DentistAppointments' => $DentistAppointments,
            'AssistantAppointments' => $AssistantAppointments,
        ]);
    }
}
