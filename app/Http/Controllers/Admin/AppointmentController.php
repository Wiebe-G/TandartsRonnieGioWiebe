<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\User;
use App\Support\AppointmentConflicts;
use App\Support\AppointmentEditOptions;
use App\Support\OpeningHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin version of the appointment pages: overview, planning on free time slots, details.
 */
class AppointmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/appointments/index', [
            'appointments' => Appointment::query()
                ->with([
                    'customer:id,firstname,lastname',
                    'dentist:id,firstname,lastname',
                    'room',
                    'treatments:treatment_id,name',
                ])
                ->orderBy('date')
                ->orderBy('starttime')
                ->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/appointments/create', [
            'customers' => User::query()
                ->where('role_id', Role::customerId())
                ->where('status', 'active')
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get(['id', 'firstname', 'lastname', 'employee_id']),
            'treatments' => Treatment::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['treatment_id', 'name', 'price', 'duration']),
            'options' => AppointmentEditOptions::toArray(),
            // Taken times on the chosen day, to show the free time slots
            'busy' => $this->busyOn($request->string('date')->value()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::customerId())],
            'treatment_id' => ['required', Rule::exists(Treatment::class, 'treatment_id')->where('active', true)],
            'dentist_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            'assistant_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::ASSISTANT))],
            'room_id' => ['required', Rule::exists(Room::class, 'room_id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'starttime' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'customer_id.required' => 'Kies een klant.',
            'treatment_id.required' => 'Kies een behandeling.',
            'dentist_id.required' => 'Kies een tandarts.',
            'assistant_id.required' => 'Kies een assistent.',
            'room_id.required' => 'Kies een kamer.',
            'starttime.required' => 'Kies een tijd.',
        ]);

        $treatment = Treatment::query()->findOrFail($validated['treatment_id']);
        $start = $validated['starttime'].':00';
        $end = OpeningHours::endFor($start, (int) $treatment->duration);

        $this->ensurePlannable($validated['date'], $start, $end, $validated);

        $appointment = DB::transaction(function () use ($validated, $start, $end, $treatment) {
            $appointment = Appointment::create([
                'customer_id' => $validated['customer_id'],
                'dentist_id' => $validated['dentist_id'],
                'assistant_id' => $validated['assistant_id'],
                'room_id' => $validated['room_id'],
                'date' => $validated['date'],
                'starttime' => $start,
                'endtime' => $end,
                'status' => 'scheduled',
                'note' => $validated['note'] ?? '',
            ]);

            DB::table('appointment_treatment')->insert([
                'appointment_id' => $appointment->appointment_id,
                'treatment_id' => $treatment->treatment_id,
            ]);

            return $appointment;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Afspraak ingepland.']);

        return to_route('admin.appointments.show', $appointment);
    }

    public function show(Request $request, Appointment $appointment): Response
    {
        return Inertia::render('admin/appointments/show', [
            'appointment' => $appointment->load(['customer', 'dentist', 'assistant', 'room', 'treatments']),
            'rooms' => Room::query()->orderBy('name')->get(['room_id', 'name']),
            'statuses' => AppointmentEditOptions::STATUSES,
            // Taken times on the day the appointment is moved to
            'busy' => $this->busyOn($request->string('date')->value() ?: $appointment->date->toDateString()),
        ]);
    }

    /**
     * Reschedule an appointment, or change its status or note.
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'starttime' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(AppointmentEditOptions::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['starttime.required' => 'Kies een tijd.']);

        $duration = (int) ($appointment->treatments()->first()?->duration ?? OpeningHours::SLOT_MINUTES);
        $start = $validated['starttime'].':00';
        $end = OpeningHours::endFor($start, $duration);

        $this->ensurePlannable($validated['date'], $start, $end, [
            'dentist_id' => $appointment->dentist_id,
            'assistant_id' => $appointment->assistant_id,
            'room_id' => $appointment->room_id,
        ], $appointment);

        $appointment->update([
            'date' => $validated['date'],
            'starttime' => $start,
            'endtime' => $end,
            'status' => $validated['status'],
            'note' => $validated['note'] ?? '',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Afspraak aangepast.']);

        return to_route('admin.appointments.show', $appointment);
    }

    public function updateRoom(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', Rule::exists(Room::class, 'room_id')],
        ], ['room_id.required' => 'Kies een kamer.']);

        $conflicts = new AppointmentConflicts($appointment->date, $appointment->starttime, $appointment->endtime, $appointment);
        if ($conflicts->roomIsTaken($validated['room_id'])) {
            throw ValidationException::withMessages(['room_id' => 'Deze kamer is op dit tijdstip al bezet.']);
        }

        $appointment->update(['room_id' => $validated['room_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kamer toegewezen.']);

        return back();
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        DB::transaction(function () use ($appointment) {
            // The treatment link restricts deleting the appointment, so it goes first
            DB::table('appointment_treatment')->where('appointment_id', $appointment->appointment_id)->delete();
            $appointment->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Afspraak verwijderd.']);

        return to_route('admin.appointments.index');
    }

    /**
     * Weekdays only, on the half hour, between 9:00 and 17:00, and nobody (or no room) booked twice.
     *
     * @param  array<string, mixed>  $people  dentist_id, assistant_id and room_id
     */
    private function ensurePlannable(string $date, string $start, string $end, array $people, ?Appointment $ignore = null): void
    {
        if (! OpeningHours::isWorkday($date)) {
            throw ValidationException::withMessages(['date' => OpeningHours::WEEKEND_MESSAGE]);
        }
        if (! OpeningHours::onSlot($start)) {
            throw ValidationException::withMessages(['starttime' => OpeningHours::SLOT_MESSAGE]);
        }
        if (! OpeningHours::fits($start, $end)) {
            throw ValidationException::withMessages(['starttime' => OpeningHours::MESSAGE]);
        }

        $conflicts = new AppointmentConflicts($date, $start, $end, $ignore);
        if ($conflicts->personIsBusy([$people['dentist_id'] ?? null])) {
            throw ValidationException::withMessages(['starttime' => 'De tandarts heeft op dit tijdstip al een afspraak.']);
        }
        if ($conflicts->personIsBusy([$people['assistant_id'] ?? null])) {
            throw ValidationException::withMessages(['starttime' => 'De assistent heeft op dit tijdstip al een afspraak.']);
        }
        if ($conflicts->roomIsTaken($people['room_id'] ?? null)) {
            throw ValidationException::withMessages(['starttime' => 'De kamer is op dit tijdstip al bezet.']);
        }
    }

    /**
     * Appointments on a day that take up time (cancelled ones do not).
     *
     * @return Collection<int, Appointment>
     */
    private function busyOn(?string $date): Collection
    {
        if (! $date || ! strtotime($date)) {
            return collect();
        }

        return Appointment::query()
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->get(['appointment_id', 'dentist_id', 'assistant_id', 'room_id', 'starttime', 'endtime']);
    }
}
