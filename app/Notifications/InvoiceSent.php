<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invoice for a completed appointment, emailed to the customer.
 */
class InvoiceSent extends Notification
{
    public function __construct(public Invoice $invoice)
    {
        // Always Dutch, also the day and month names in the dates.
        $this->locale = 'nl';
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing(['appointment.dentist', 'appointment.treatments']);
        $appointment = $invoice->appointment;
        $euro = fn ($amount) => '€ '.number_format((float) $amount, 2, ',', '.');

        $mail = (new MailMessage)
            ->subject('Factuur '.$invoice->number().' van '.config('app.name'))
            ->greeting("Beste {$notifiable->firstname},")
            ->line('Hierbij ontvangt u de factuur voor uw afspraak op '.$appointment->date->isoFormat('dddd D MMMM YYYY').' bij '.$appointment->dentist->name.'.')
            ->line('**Factuurnummer:** '.$invoice->number())
            ->line('**Factuurdatum:** '.$invoice->invoicedate->isoFormat('D MMMM YYYY'));

        foreach ($appointment->treatments as $treatment) {
            $mail->line('**'.$treatment->name.':** '.$euro($treatment->price));
        }

        return $mail
            ->line('**Totaal:** '.$euro($invoice->amount))
            ->line('Wilt u het bedrag uiterlijk '.$invoice->expiration_date->isoFormat('D MMMM YYYY').' overmaken onder vermelding van het factuurnummer?')
            ->action('Bekijk uw factuur', route('customer.invoices.show', $invoice))
            ->salutation('Met vriendelijke groet, '.config('app.name'));
    }
}
