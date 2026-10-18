<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];

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
            'options' => [
                'dentists' => $employees->where('role_id', Role::idFor(Role::DENTIST))->values(),
                'staff' => $employees->values(),
                'rooms' => Room::query()->orderBy('name')->get(['room_id', 'name']),
                'statuses' => self::STATUSES,
            ],
        ]);
    }

    /**
     * Update: move or reassign an appointment from the roster.
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'starttime' => ['required', 'date_format:H:i'],
            'endtime' => ['required', 'date_format:H:i', 'after:starttime'],
            'dentist_id' => ['required', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            'assistant_id' => ['nullable', 'different:dentist_id', Rule::exists(User::class, 'id')->whereNot('role_id', Role::customerId())],
            'room_id' => ['nullable', Rule::exists(Room::class, 'room_id')],
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Times are stored as HH:MM:SS, so compare and save them in that format
        $validated['starttime'] .= ':00';
        $validated['endtime'] .= ':00';

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
        $overlapping = Appointment::query()
            ->whereKeyNot($appointment->getKey())
            ->whereDate('date', $validated['date'])
            ->where('starttime', '<', $validated['endtime'])
            ->where('endtime', '>', $validated['starttime'])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $people = array_filter([$validated['dentist_id'], $validated['assistant_id'] ?? null]);

        foreach ($overlapping as $other) {
            if (array_intersect($people, array_filter([$other->dentist_id, $other->assistant_id]))) {
                throw ValidationException::withMessages([
                    'starttime' => 'De tandarts of assistent heeft op dit tijdstip al een andere afspraak.',
                ]);
            }

            if (! empty($validated['room_id']) && (int) $other->room_id === (int) $validated['room_id']) {
                throw ValidationException::withMessages([
                    'room_id' => 'Deze kamer is op dit tijdstip al bezet.',
                ]);
            }
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
