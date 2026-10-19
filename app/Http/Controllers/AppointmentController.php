<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use App\Support\AppointmentConflicts;
use App\Support\OpeningHours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $Patients = User::query()->get()->where('role_id', Role::customerId())->values()->toArray();
        $Treatments = Treatment::query()->get()->where('active', true)->values()->toArray();

        return Inertia::render('Appointments/Create', [
            'Customers' => $Patients,
            'Treatments' => $Treatments,
            // Met rol, zodat de pagina op rolnaam kan filteren
            'Users' => User::query()->with('role')->get()->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Customer_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::customerId())],
            'Dentist_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            // Een assistent is optioneel
            'Assistant_id' => ['nullable', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::ASSISTANT))],
            'Date' => ['required', 'date'],
            'Type' => ['required', 'exists:treatment,treatment_id'],
            'Note' => ['nullable', 'string', 'max:255'],
        ], [
            'Customer_id.required' => 'Kies een klant.',
            'Dentist_id.required' => 'Kies een tandarts.',
            'Type.required' => 'Kies een behandeling.',
        ]);

        $Datum = $validated['Date'];
        $tijd = Carbon::parse($Datum)->timezone('Europe/Amsterdam');
        $treatment = Treatment::query()->firstWhere('treatment_id', $validated['Type']);
        $starttime = $tijd->toTimeString();
        // Afgerond naar boven op een half uur
        $endtime = OpeningHours::endFor($starttime, (int) $treatment->duration);

        $this->checkPlanning($tijd->toDateString(), $starttime, $endtime, $validated['Dentist_id'], $validated['Assistant_id'] ?? null);

        $Customer = User::query()->find($request->Customer_id);
        $appointment = Appointment::create([
            'customer_id' => $Customer->id,
            'dentist_id' => $validated['Dentist_id'],
            'assistant_id' => $validated['Assistant_id'] ?? null,
            'date' => $tijd->toDateString(),
            'starttime' => $starttime,
            'endtime' => $endtime,
            'status' => 'scheduled',
            'note' => $validated['Note'] ?? '',
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
            ->firstOrFail();

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
            'Note' => ['nullable', 'string', 'max:255'],
            'Date' => ['required', 'date'],
            'Starttime' => ['required', 'date_format:H:i,H:i:s'],
        ]);
        $Appointment = Appointment::query()->with('treatments')->find($validated['Appointment_Id']);

        // Een afgeronde afspraak ligt vast: alleen de notitie mag nog veranderen
        if ($Appointment->isLocked()) {
            $Appointment->update(['note' => $validated['Note'] ?? '']);

            return redirect()->route('appointments')
                ->with('success', 'Notitie aangepast');
        }

        $tijd = Carbon::parse($validated['Starttime']);
        $startTime = $tijd->toTimeString();
        $endTime = OpeningHours::endFor($startTime, (int) ($Appointment['treatments'][0]['duration'] ?? OpeningHours::SLOT_MINUTES));

        $this->checkPlanning(Carbon::parse($validated['Date'])->toDateString(), $startTime, $endTime, $Appointment->dentist_id, $Appointment->assistant_id, $Appointment);

        // De status blijft zoals hij was
        $Appointment->update([
            'note' => $validated['Note'] ?? '',
            'date' => $validated['Date'],
            'starttime' => $startTime,
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
        // Een afspraak met een factuur blijft bestaan
        if ($appointment->invoice()->exists()) {
            throw ValidationException::withMessages(['id' => 'Deze afspraak heeft al een factuur en kan niet meer verwijderd worden.']);
        }
        // De koppeling met de behandeling houdt het verwijderen tegen, dus die gaat eerst
        DB::table('appointment_treatment')->where('appointment_id', $appointment->appointment_id)->delete();
        $appointment->delete();

        return redirect()->route('appointments')
            ->with('success', 'Afspraak succesvol verwijderd');
    }

    /**
     * Alleen op werkdagen, tussen 9:00 en 17:00, op het hele of halve uur, en niemand dubbel geboekt.
     */
    private function checkPlanning(string $date, string $start, string $end, $dentistId, $assistantId, ?Appointment $ignore = null): void
    {
        if (! OpeningHours::isWorkday($date)) {
            throw ValidationException::withMessages(['Date' => OpeningHours::WEEKEND_MESSAGE]);
        }
        if (! OpeningHours::onSlot($start)) {
            throw ValidationException::withMessages(['Date' => OpeningHours::SLOT_MESSAGE]);
        }
        if (! OpeningHours::fits($start, $end)) {
            throw ValidationException::withMessages(['Date' => OpeningHours::MESSAGE]);
        }

        $conflicts = new AppointmentConflicts($date, $start, $end, $ignore);
        if ($conflicts->personIsBusy([$dentistId])) {
            throw ValidationException::withMessages(['Dentist_id' => 'De tandarts heeft op dit tijdstip al een afspraak.']);
        }
        if ($conflicts->personIsBusy([$assistantId])) {
            throw ValidationException::withMessages(['Assistant_id' => 'De assistent heeft op dit tijdstip al een afspraak.']);
        }
        if ($ignore && $conflicts->roomIsTaken($ignore->room_id)) {
            throw ValidationException::withMessages(['Date' => 'De kamer is op dit tijdstip al bezet.']);
        }
    }
}
