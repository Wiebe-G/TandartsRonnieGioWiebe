<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // oké goed dan is dit maar een object >:(
        $Patients = User::query()->get()->where('role_id', 1)->values()->toArray();
        $Treatments = Treatment::query()->get()->where('active', true)->values()->toArray();

        return Inertia::render('Appointments/Create', [
            'Customers' => $Patients,
            'Treatments' => $Treatments,
            'Users' => User::query()->get()->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Customer_id' => ['required', 'exists:users,id'],
            'Dentist_id' => ['required', 'exists:users,id'],
            'Assistant_id' => ['required', 'exists:users,id'],
            'Date' => ['required', 'date'],
            'Type' => ['required', 'exists:treatment,treatment_id'],
            'Note' => ['required', 'string', 'max:255'],
        ]);

        $Datum = $validated['Date'];
        $tijd = Carbon::parse($Datum)->timezone('Europe/Amsterdam');
        $treatment = Treatment::query()->firstWhere('treatment_id', $validated['Type']);

        $Customer = User::query()->find($request->Customer_id);
        $appointment = Appointment::create([
            'customer_id' => $Customer->id,
            'dentist_id' => $validated['Dentist_id'],
            'assistant_id' => $validated['Assistant_id'],
            'date' => $tijd->toDateString(),
            'starttime' => $tijd->toTimeString(),
            'endtime' => $tijd->addMinutes($treatment->duration)->toTimeString(),
            'status' => 'Nog niet denk ik idk',
            'note' => $validated['Note'],
        ]);

        DB::table('appointment_treatment')->insert([
            'appointment_id' => $appointment->appointment_id,
            'treatment_id' => $validated['Type'],
        ]);

        return redirect()->route('appointments')
            ->with('success', 'Appointment succesvol toegevoegd');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $appointment = Appointment::query()
            ->where('appointment_id', $id)
            ->with(['customer', 'treatments'])
            ->get()
            ->first();

        return Inertia::render('Appointments/Show', [
            'Appointment' => $appointment,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
