<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Support\AppointmentConflicts;
use App\Support\AppointmentEditOptions;
use App\Support\OpeningHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Read: pick an employee and see their appointments for one week.
     */
    public function index(Request $request): Response
    {
        $week = $this->weekStart($request->string('week')->value());

        $employees = User::query()
            ->where('role_id', '!=', Role::customerId())
            ->with('role')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get(['id', 'role_id', 'firstname', 'lastname', 'status']);

        $selected = $employees->firstWhere('id', $request->integer('employee'));

        return Inertia::render('admin/calendar', [
            'employees' => $employees,
            'selected' => $selected,
            'week' => [
                'start' => $week->toDateString(),
                'previous' => $week->subWeek()->toDateString(),
                'next' => $week->addWeek()->toDateString(),
                'days' => $selected ? $this->roster($selected, $week) : [],
            ],
            'options' => AppointmentEditOptions::toArray(),
        ]);
    }

    /**
     * Update: move or reassign an appointment from the roster.
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        // A completed appointment is locked: only the note can still be changed
        if ($appointment->isLocked()) {
            $appointment->update(['note' => $request->validate(['note' => ['nullable', 'string', 'max:255']])['note'] ?? '']);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Notitie aangepast.']);

            return back();
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'starttime' => ['required', 'date_format:H:i'],
            'endtime' => ['required', 'date_format:H:i', 'after:starttime'],
            'dentist_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            'assistant_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::ASSISTANT))],
            'room_id' => ['required', Rule::exists(Room::class, 'room_id')],
            'status' => ['required', Rule::in(AppointmentEditOptions::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Times are stored as HH:MM:SS, so compare and save them in that format
        $validated['starttime'] .= ':00';
        $validated['endtime'] .= ':00';

        if (! OpeningHours::isWorkday($validated['date'])) {
            throw ValidationException::withMessages(['date' => OpeningHours::WEEKEND_MESSAGE]);
        }
        if (! OpeningHours::onSlot($validated['starttime']) || ! OpeningHours::onSlot($validated['endtime'])) {
            throw ValidationException::withMessages(['starttime' => OpeningHours::SLOT_MESSAGE]);
        }
        if (! OpeningHours::fits($validated['starttime'], $validated['endtime'])) {
            throw ValidationException::withMessages(['starttime' => OpeningHours::MESSAGE]);
        }

        $this->ensureNoOverlap($appointment, $validated);

        $appointment->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Afspraak bijgewerkt.']);

        return back();
    }

    /**
     * Monday to Sunday of the given week, each with that employee's appointments.
     *
     * @return array<int, array<string, mixed>>
     */
    private function roster(User $employee, CarbonImmutable $week): array
    {
        $appointments = Appointment::query()
            ->where(fn ($query) => $query
                ->where('dentist_id', $employee->id)
                ->orWhere('assistant_id', $employee->id))
            ->whereBetween('date', [$week->toDateString(), $week->addDays(6)->toDateString()])
            ->with([
                'customer:id,firstname,lastname',
                'dentist:id,firstname,lastname',
                'assistant:id,firstname,lastname',
                'room',
                'treatments:treatment_id,name',
            ])
            ->orderBy('starttime')
            ->get()
            ->groupBy(fn (Appointment $appointment) => CarbonImmutable::parse($appointment->date)->toDateString());

        return collect(range(0, 6))
            ->map(function (int $offset) use ($week, $appointments) {
                $date = $week->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'appointments' => $appointments->get($date, collect())->values(),
                ];
            })
            ->all();
    }

    /**
     * Nobody can be in two appointments at once, and neither can a room.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureNoOverlap(Appointment $appointment, array $validated): void
    {
        $conflicts = new AppointmentConflicts($validated['date'], $validated['starttime'], $validated['endtime'], $appointment);

        if ($conflicts->personIsBusy([$validated['dentist_id'], $validated['assistant_id'] ?? null])) {
            throw ValidationException::withMessages([
                'starttime' => 'De tandarts of assistent heeft op dit tijdstip al een andere afspraak.',
            ]);
        }

        if ($conflicts->roomIsTaken($validated['room_id'] ?? null)) {
            throw ValidationException::withMessages([
                'room_id' => 'Deze kamer is op dit tijdstip al bezet.',
            ]);
        }
    }

    private function weekStart(?string $date): CarbonImmutable
    {
        try {
            $day = $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();
        } catch (\Throwable) {
            $day = CarbonImmutable::today();
        }

        return $day->startOfWeek(CarbonImmutable::MONDAY);
    }
}
