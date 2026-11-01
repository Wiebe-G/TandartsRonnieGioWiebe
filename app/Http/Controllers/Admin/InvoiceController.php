<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Notifications\InvoiceSent;
use App\Support\InvoicePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invoices for completed appointments: made and sent by assistants and admins.
 */
class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/invoices/index', [
            // Completed appointments that do not have an invoice yet (assistants: only their own)
            'toInvoice' => Appointment::query()
                ->visibleTo($request->user())
                ->where('status', Appointment::COMPLETED)
                ->whereDoesntHave('invoice')
                ->with(['customer:id,firstname,lastname,email', 'dentist:id,firstname,lastname', 'treatments:treatment_id,name,price'])
                ->orderByDesc('date')
                ->orderByDesc('starttime')
                ->get()
                ->map(fn (Appointment $appointment) => [
                    ...$appointment->toArray(),
                    'total' => $this->totalFor($appointment),
                ]),
            'invoices' => Invoice::query()
                ->whereHas('appointment', fn ($query) => $query->visibleTo($request->user()))
                ->with(['appointment.customer:id,firstname,lastname,email', 'appointment.treatments:treatment_id,name'])
                ->orderByRaw('sent_at is not null') // not sent yet first
                ->orderByDesc('invoicedate')
                ->orderByDesc('invoice_id')
                ->get()
                ->map(fn (Invoice $invoice) => InvoicePresenter::summary($invoice)),
        ]);
    }

    /**
     * Make the invoice for a completed appointment; the amount is fixed from the treatment prices now.
     */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->isVisibleTo($request->user()), 403);

        if ($appointment->status !== Appointment::COMPLETED) {
            throw ValidationException::withMessages(['invoice' => 'Alleen afgeronde afspraken kunnen gefactureerd worden.']);
        }

        if ($appointment->invoice()->exists()) {
            throw ValidationException::withMessages(['invoice' => 'Voor deze afspraak is al een factuur gemaakt.']);
        }

        $invoice = Invoice::create([
            'appointment_id' => $appointment->appointment_id,
            'invoicedate' => today(),
            'expiration_date' => today()->addDays(Invoice::PAYMENT_DAYS),
            'amount' => $this->totalFor($appointment),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Factuur '.$invoice->number().' gemaakt.']);

        return to_route('admin.invoices.show', $invoice);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->ensureVisible($request, $invoice);

        return Inertia::render('admin/invoices/show', [
            'invoice' => InvoicePresenter::detail($invoice),
        ]);
    }

    /**
     * Mark the invoice as paid, or back to pending when that was a mistake.
     */
    public function updatePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureVisible($request, $invoice);

        $paid = $request->validate(['paid' => ['required', 'boolean']])['paid'];

        $invoice->update(['paid_at' => $paid ? now() : null]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Factuur '.$invoice->number().($paid ? ' is betaald.' : ' staat weer open.'),
        ]);

        return back();
    }

    /**
     * Email the invoice to the customer. Sending again is allowed (e.g. when it got lost).
     */
    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureVisible($request, $invoice);

        $customer = $invoice->appointment->customer;

        $customer->notify(new InvoiceSent($invoice));
        $invoice->update(['sent_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Factuur '.$invoice->number().' verstuurd naar '.$customer->email.'.']);

        return back();
    }

    /**
     * Assistants only handle invoices for their own appointments.
     */
    private function ensureVisible(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->appointment->isVisibleTo($request->user()), 403);
    }

    private function totalFor(Appointment $appointment): string
    {
        return number_format((float) $appointment->treatments->sum('price'), 2, '.', '');
    }
}
