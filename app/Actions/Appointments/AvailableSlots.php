<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out which start times are still free for a dentist on a given day.
 */
class AvailableSlots
{
    public const OPENS = '08:00';

    public const CLOSES = '17:00';

    public const STEP_MINUTES = 15;

    /**
     * Total length of the chosen treatments, rounded up to whole slots.
     *
     * @param  Collection<int, Treatment>  $treatments
     */
    public static function durationFor(Collection $treatments): int
    {
        $minutes = max((int) $treatments->sum('duration'), self::STEP_MINUTES);

        return (int) ceil($minutes / self::STEP_MINUTES) * self::STEP_MINUTES;
    }

    /**
     * Start times ("H:i") where both the dentist and the customer are free.
     *
     * @return list<string>
     */
    public function for(User $dentist, User $customer, Carbon $date, int $duration): array
    {
        if ($date->isWeekend() || $date->lt(today())) {
            return [];
        }

        $busy = Appointment::query()
            ->whereDate('date', $date)
            ->where('status', '!=', Appointment::CANCELLED)
            ->where(fn ($query) => $query
                ->where('dentist_id', $dentist->id)
                ->orWhere('customer_id', $customer->id))
            ->get(['starttime', 'endtime'])
            ->map(fn (Appointment $appointment) => [
                substr($appointment->starttime, 0, 5),
                substr($appointment->endtime, 0, 5),
            ]);

        $slots = [];
        $start = $date->copy()->setTimeFromTimeString(self::OPENS);
        $closes = $date->copy()->setTimeFromTimeString(self::CLOSES);

        while ($start->copy()->addMinutes($duration)->lte($closes)) {
            $from = $start->format('H:i');
            $until = $start->copy()->addMinutes($duration)->format('H:i');

            $overlaps = $busy->contains(fn (array $taken) => $from < $taken[1] && $until > $taken[0]);

            if (! $overlaps && $start->isFuture()) {
                $slots[] = $from;
            }

            $start->addMinutes(self::STEP_MINUTES);
        }

        return $slots;
    }
}
