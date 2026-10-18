<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation mail sent to the customer after an appointment is cancelled.
 */
class AppointmentCancelled extends Notification
{
    public function __construct(public Appointment $appointment)
    {
        // Always Dutch, also the day and month names in the date.
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
        $appointment = $this->appointment->loadMissing('dentist');

        return (new MailMessage)
            ->subject('Uw afspraak is geannuleerd')
            ->greeting("Beste {$notifiable->firstname},")
            ->line('Uw afspraak op '.$appointment->startsAt()->isoFormat('dddd D MMMM YYYY')
                .' om '.substr($appointment->starttime, 0, 5)
                .' bij '.$appointment->dentist->name.' is geannuleerd.')
            ->action('Nieuwe afspraak maken', route('appointments.create'))
            ->salutation('Met vriendelijke groet, '.config('app.name'));
    }
}
