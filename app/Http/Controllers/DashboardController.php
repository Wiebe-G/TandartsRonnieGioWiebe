<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Customers get their own start screen; employees keep the regular dashboard.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isCustomer()) {
            // The regular dashboard needs its appointments (and statistics for admins)
            return app(RouteController::class)->dashboard();
        }

        $appointments = $user->appointments()
            ->with(['dentist', 'treatments'])
            ->orderBy('date')
            ->orderBy('starttime')
            ->get();

        [$upcoming, $past] = $appointments->partition(
            fn (Appointment $appointment) => $appointment->status === Appointment::SCHEDULED
                && $appointment->date->gte(today()),
        );

        $dentist = $user->employee;

        return Inertia::render('customer/dashboard', [
            'dentist' => $dentist ? [
                'name' => $dentist->name,
                'email' => $dentist->email,
                'phonenumber' => $dentist->phonenumber,
            ] : null,
            'upcoming' => $upcoming->map($this->present(...))->values(),
            'recent' => $past->reverse()->take(5)->map($this->present(...))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Appointment $appointment): array
    {
        return [
            'id' => $appointment->appointment_id,
            'date' => $appointment->date->toDateString(),
            'starttime' => substr($appointment->starttime, 0, 5),
            'endtime' => substr($appointment->endtime, 0, 5),
            'status' => $appointment->status,
            'note' => $appointment->note,
            'dentist' => $appointment->dentist->name,
            'treatments' => $appointment->treatments->pluck('name'),
        ];
    }
}
