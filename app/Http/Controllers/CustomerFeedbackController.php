<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's own feedback: what they still can review, and what they already said.
 * Saving goes through FeedbackController (appointments.feedback), which only accepts their own appointments.
 */
class CustomerFeedbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $customer */
        $customer = $request->user();
        abort_unless($customer->isCustomer(), 403);

        $present = fn (Appointment $appointment) => [
            'id' => $appointment->appointment_id,
            'date' => $appointment->date->toDateString(),
            'starttime' => substr($appointment->starttime, 0, 5),
            'dentist' => $appointment->dentist?->name,
            'treatments' => $appointment->treatments->pluck('name'),
        ];

        return Inertia::render('customer/feedback/index', [
            // Appointments that took place and do not have feedback yet
            'toReview' => $customer->appointments()
                ->past()
                ->whereDoesntHave('feedback')
                ->with(['dentist', 'treatments'])
                ->orderByDesc('date')
                ->orderByDesc('starttime')
                ->get()
                ->map($present),
            // Only the feedback this customer gave
            'feedback' => Feedback::query()
                ->where('customer_id', $customer->id)
                ->with(['appointment.dentist', 'appointment.treatments'])
                ->latest('updated_at')
                ->get()
                ->map(fn (Feedback $feedback) => [
                    'id' => $feedback->feedback_id,
                    'satisfied' => $feedback->satisfied,
                    'comment' => $feedback->comment,
                    'updated_at' => $feedback->updated_at?->toIso8601String(),
                    'appointment' => $present($feedback->appointment),
                ]),
        ]);
    }
}
