<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    /**
     * Save (or change) the customer's review of an appointment that took place.
     */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->customer_id === $request->user()->id && $appointment->tookPlace(), 403);

        $validated = $request->validate([
            'satisfied' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'satisfied.required' => 'Kies of u tevreden of niet tevreden bent.',
        ], [
            'comment' => 'notitie',
        ]);

        Feedback::updateOrCreate(
            ['appointment_id' => $appointment->appointment_id],
            ['customer_id' => $appointment->customer_id, ...$validated],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bedankt voor uw feedback!']);

        return back();
    }
}
