<?php

namespace App\Http\Controllers;

use App\Actions\Appointments\AvailableSlots;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * The agenda: customers see their own appointments, employees see the whole practice.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $month = rescue(
            fn () => Carbon::createFromFormat('!Y-m', (string) $request->query('maand'))->startOfMonth(),
            today()->startOfMonth(),
            report: false,
        );

        $appointments = Appointment::query()
            ->with(['customer', 'dentist', 'treatments'])
            ->when($user->isCustomer(), fn ($query) => $query->where('customer_id', $user->id))
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->orderBy('starttime')
            ->get();

        return Inertia::render('afspraken/index', [
            'month' => $month->format('Y-m'),
            'isCustomer' => $user->isCustomer(),
            'appointments' => $appointments->map(fn (Appointment $appointment) => [
                'id' => $appointment->appointment_id,
                'date' => $appointment->date->toDateString(),
                'starttime' => substr($appointment->starttime, 0, 5),
                'endtime' => substr($appointment->endtime, 0, 5),
                'status' => $appointment->status,
                'note' => $appointment->note,
                'customer' => $appointment->customer->name,
                'dentist' => $appointment->dentist->name,
                'treatments' => $appointment->treatments->pluck('name'),
                'canCancel' => $this->canCancel($user, $appointment),
                // Upcoming, but too close to the start for the customer to cancel online.
                'cancelTooLate' => $user->isCustomer() && $appointment->isUpcoming() && ! $appointment->cancellableByCustomer(),
            ]),
            'cancelHours' => Appointment::CANCEL_HOURS,
        ]);
    }

    /**
     * The booking form. Free times are recalculated whenever the customer changes a choice.
     */
    public function create(Request $request, AvailableSlots $availableSlots): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isCustomer(), 403);

        $treatments = Treatment::where('active', true)->orderBy('name')->get();
        // Customers can only book with the dentist assigned to them; without one they cannot book at all.
        $dentist = $user->assignedDentist();
        $dentists = collect($dentist ? [$dentist] : []);

        $chosenTreatments = $treatments->whereIn('treatment_id', array_map('intval', (array) $request->query('treatments', [])));
        $date = rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->query('date')), null, report: false);
        $duration = AvailableSlots::durationFor($chosenTreatments);

        $slots = [];

        if ($dentist && $date && $chosenTreatments->isNotEmpty()) {
            $slots = $availableSlots->for($dentist, $user, $date, $duration);
        }

        return Inertia::render('afspraken/nieuw', [
            'treatments' => $treatments->map(fn (Treatment $treatment) => [
                'id' => $treatment->treatment_id,
                'name' => $treatment->name,
                'description' => $treatment->description,
                'duration' => $treatment->duration,
                'price' => $treatment->price,
            ])->values(),
            'dentists' => $dentists->map(fn (User $dentist) => [
                'id' => $dentist->id,
                'name' => $dentist->name,
            ])->values(),
            'defaultDentist' => $dentist?->id,
            // Profile details still missing; booking is only possible when this is empty
            'missing' => $user->missingBookingDetails(),
            'duration' => $duration,
            'slots' => $slots,
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $start = Carbon::parse($request->string('date').' '.$request->string('starttime'));

        $appointment = Appointment::create([
            'customer_id' => $request->user()->id,
            'dentist_id' => $request->integer('dentist_id'),
            'date' => $start->toDateString(),
            'starttime' => $start->format('H:i'),
            'endtime' => $start->copy()->addMinutes($request->duration())->format('H:i'),
            'status' => Appointment::SCHEDULED,
            'note' => $request->input('note'),
        ]);

        $appointment->treatments()->attach($request->input('treatments'));

        $request->user()->notify(new AppointmentBooked($appointment));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Uw afspraak is ingepland. U ontvangt een bevestiging per e-mail.']);

        return to_route('appointments.index', ['maand' => $start->format('Y-m')]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_if($user->isCustomer() && $appointment->customer_id !== $user->id, 403);

        if (! $this->canCancel($user, $appointment)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Deze afspraak kan niet meer online geannuleerd worden. Neem contact op met de praktijk.']);

            return back();
        }

        $appointment->update(['status' => Appointment::CANCELLED]);

        $appointment->customer->notify(new AppointmentCancelled($appointment));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'De afspraak is geannuleerd. U ontvangt een bevestiging per e-mail.']);

        return back();
    }

    /**
     * Customers can cancel their own appointments up to 48 hours before; employees any upcoming one.
     */
    private function canCancel(User $user, Appointment $appointment): bool
    {
        if ($user->isCustomer()) {
            return $appointment->customer_id === $user->id && $appointment->cancellableByCustomer();
        }

        return $appointment->isUpcoming();
    }
}
