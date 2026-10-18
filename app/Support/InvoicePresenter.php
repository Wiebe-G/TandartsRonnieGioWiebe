<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * How an invoice is sent to the pages; shared by the staff pages and the customer's own invoice pages.
 */
class InvoicePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Invoice $invoice): array
    {
        return [
            'invoice_id' => $invoice->invoice_id,
            'number' => $invoice->number(),
            'invoicedate' => $invoice->invoicedate->toDateString(),
            'expiration_date' => $invoice->expiration_date->toDateString(),
            'amount' => $invoice->amount,
            'sent_at' => $invoice->sent_at?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            // pending (openstaand) or paid (betaald); overdue is a pending invoice past its payment date
            'status' => $invoice->isPaid() ? 'paid' : 'pending',
            'overdue' => $invoice->isOverdue(),
            'customer_name' => $invoice->appointment->customer?->name,
            'customer_email' => $invoice->appointment->customer?->email,
            'treatments' => $invoice->appointment->treatments->pluck('name'),
        ];
    }

    /**
     * Everything shown on the invoice itself.
     *
     * @return array<string, mixed>
     */
    public static function detail(Invoice $invoice): array
    {
        $invoice->loadMissing(['appointment.customer.postcode', 'appointment.dentist', 'appointment.treatments']);
        $appointment = $invoice->appointment;
        $customer = $appointment->customer;

        return [
            ...self::summary($invoice),
            'appointment' => [
                'date' => $appointment->date->toDateString(),
                'starttime' => substr($appointment->starttime, 0, 5),
                'dentist' => $appointment->dentist?->name,
                'treatments' => $appointment->treatments->map(fn ($treatment) => [
                    'name' => $treatment->name,
                    'price' => $treatment->price,
                ]),
            ],
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'address' => $customer->postcode ? [
                    'street' => $customer->postcode->street.' '.$customer->postcode->house_number,
                    'city' => $customer->postcode->postcode().' '.$customer->postcode->city,
                ] : null,
            ],
        ];
    }
}
