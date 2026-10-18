<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Support\InvoicePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's own invoices: only the ones that were sent to them.
 */
class CustomerInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('customer/invoices/index', [
            'invoices' => $this->sentTo($request->user())
                ->with(['appointment.customer', 'appointment.treatments'])
                ->orderByRaw('paid_at is not null') // still to pay first
                ->orderByDesc('invoicedate')
                ->orderByDesc('invoice_id')
                ->get()
                ->map(fn (Invoice $invoice) => InvoicePresenter::summary($invoice)),
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        // Someone else's invoice, or one that was not sent yet, does not exist for this customer
        abort_unless($this->sentTo($request->user())->whereKey($invoice->getKey())->exists(), 404);

        return Inertia::render('customer/invoices/show', [
            'invoice' => InvoicePresenter::detail($invoice),
        ]);
    }

    /**
     * The customer pays their invoice. This is a demo payment (school project): no payment provider,
     * the payment is checked here and recorded straight away.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($this->sentTo($request->user())->whereKey($invoice->getKey())->exists(), 404);

        if ($invoice->isPaid()) {
            throw ValidationException::withMessages([
                'payment' => 'Deze factuur is al betaald op '.$invoice->paid_at->locale('nl')->isoFormat('D MMMM YYYY').'.',
            ]);
        }

        $invoice->update(['paid_at' => now()]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Betaling van € '.number_format((float) $invoice->amount, 2, ',', '.').' ontvangen. Bedankt!',
        ]);

        return to_route('customer.invoices.show', $invoice);
    }

    /**
     * @return Builder<Invoice>
     */
    private function sentTo(User $customer): Builder
    {
        abort_unless($customer->isCustomer(), 403);

        return Invoice::query()
            ->whereNotNull('sent_at')
            ->whereHas('appointment', fn ($query) => $query->where('customer_id', $customer->id));
    }
}
