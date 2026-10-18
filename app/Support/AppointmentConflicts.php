<?php

namespace App\Support;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Finds appointments that overlap a time slot, so nobody (and no room) is booked twice.
 * Cancelled appointments and back-to-back appointments do not count as overlapping.
 */
class AppointmentConflicts
{
    /** @var Collection<int, Appointment> */
    private Collection $overlapping;

    /**
     * @param  DateTimeInterface|string  $date  a date, or YYYY-MM-DD
     * @param  string  $start  HH:MM:SS
     * @param  string  $end  HH:MM:SS
     */
    public function __construct(DateTimeInterface|string $date, string $start, string $end, ?Appointment $ignore = null)
    {
        $this->overlapping = Appointment::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            // Always compare as YYYY-MM-DD; a date object would otherwise become "2026-10-21 00:00:00"
            ->whereDate('date', CarbonImmutable::parse($date)->toDateString())
            ->where('starttime', '<', $end)
            ->where('endtime', '>', $start)
            ->where('status', '!=', 'cancelled')
            ->get();
    }

    /**
     * Whether one of these people (dentist or assistant) is already in another appointment.
     *
     * @param  array<int, int|string|null>  $userIds
     */
    public function personIsBusy(array $userIds): bool
    {
        $userIds = array_map('intval', array_filter($userIds));

        return $this->overlapping->contains(fn (Appointment $other) => array_intersect(
            $userIds,
            array_map('intval', array_filter([$other->dentist_id, $other->assistant_id])),
        ) !== []);
    }

    public function roomIsTaken(int|string|null $roomId): bool
    {
        return $roomId !== null && $roomId !== ''
            && $this->overlapping->contains(fn (Appointment $other) => (int) $other->room_id === (int) $roomId);
    }
}
