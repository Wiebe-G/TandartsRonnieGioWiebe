<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation mail sent to the customer right after booking.
 */
class AppointmentBooked extends Notification
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
        $appointment = $this->appointment->loadMissing(['dentist', 'treatments']);

        return (new MailMessage)
            ->subject('Bevestiging van uw afspraak')
            ->greeting("Beste {$notifiable->firstname},")
            ->line('Uw afspraak bij '.config('app.name').' is ingepland.')
            ->line('**Datum:** '.$appointment->startsAt()->isoFormat('dddd D MMMM YYYY'))
            ->line('**Tijd:** '.substr($appointment->starttime, 0, 5).' – '.substr($appointment->endtime, 0, 5))
            ->line('**Tandarts:** '.$appointment->dentist->name)
            ->line('**Behandeling:** '.$appointment->treatments->pluck('name')->join(', '))
            ->action('Bekijk uw agenda', route('appointments.index', ['maand' => $appointment->date->format('Y-m')]))
            ->line('Kunt u niet komen? Annuleer dan uiterlijk '.Appointment::CANCEL_HOURS.' uur van tevoren via uw agenda.')
            ->salutation('Met vriendelijke groet, '.config('app.name'));
    }
}
