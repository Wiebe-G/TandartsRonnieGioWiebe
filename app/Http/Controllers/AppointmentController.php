<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'Users' => User::query()->with('role')->get()->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Customer_id' => ['required', 'exists:users,id'],
            'Dentist_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            // Een assistent is optioneel
            'Assistant_id' => ['nullable', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::ASSISTANT))],
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
            'assistant_id' => $validated['Assistant_id'] ?? null,
            'date' => $tijd->toDateString(),
            'starttime' => $tijd->toTimeString(),
            'endtime' => $tijd->addMinutes($treatment->duration)->toTimeString(),
            'status' => 'scheduled',
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
            ->with(['customer', 'treatments', 'dentist', 'assistant'])
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
    public function update(Request $request)
    {
        $validated = $request->validate([
            'Appointment_Id' => ['required', 'exists:appointment,appointment_id'],
            'Note' => ['required', 'string', 'max:255'],
            'Date' => ['required', 'string'],
            'Starttime' => ['required', 'string'],
        ]);
        $Appointment = Appointment::query()->with('treatments')->find($validated['Appointment_Id']);

        $tijd = Carbon::parse($validated['Starttime']);
        $endTime = $tijd->addMinutes($Appointment['treatments'][0]['duration'])->toTimeString();

        $Appointment->update([
            'note' => $validated['Note'],
            'date' => $validated['Date'],
            'starttime' => $validated['Starttime'],
            'endtime' => $endTime,
        ]);

        return redirect()->route('appointments')
            ->with('success', 'Afspraak succesvol aangepast');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:appointment,appointment_id'],
        ]);
        $appointment = Appointment::query()->find($validated['id']);
        abort_if(Auth::check() && auth()->user()->role_id == 1, 403);
        $appointment->delete();

        return redirect()->route('appointments')
            ->with('success', 'Afspraak succesvol verwijderd');
    }
}
