<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentHistoryController extends Controller
{
    /**
     * Every appointment the customer had, newest first, with what was done and the feedback they gave.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->isCustomer(), 403);

        $appointments = $user->appointments()
            ->past()
            ->with(['dentist', 'assistant', 'treatments', 'feedback'])
            ->orderByDesc('date')
            ->orderByDesc('starttime')
            ->get();

        return Inertia::render('behandelingen/geschiedenis', [
            'appointments' => $appointments->map(fn (Appointment $appointment) => [
                'id' => $appointment->appointment_id,
                'date' => $appointment->date->toDateString(),
                'starttime' => substr($appointment->starttime, 0, 5),
                'endtime' => substr($appointment->endtime, 0, 5),
                'note' => $appointment->note,
                'dentist' => $appointment->dentist->name,
                'treatments' => $appointment->treatments->map(fn (Treatment $treatment) => [
                    'name' => $treatment->name,
                    'price' => $treatment->price,
                ]),
                'assistant' => $appointment->assistant?->name,
                'feedback' => $appointment->feedback ? [
                    'satisfied' => $appointment->feedback->satisfied,
                    'comment' => $appointment->feedback->comment,
                ] : null,
            ]),
        ]);
    }
}
