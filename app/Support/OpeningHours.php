<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Appointments are planned on weekdays, between 9:00 and 17:00, in blocks of 30 minutes.
 */
class OpeningHours
{
    public const OPEN = '09:00:00';

    public const CLOSE = '17:00:00';

    public const SLOT_MINUTES = 30;

    public const MESSAGE = 'Een afspraak moet tussen 9:00 en 17:00 vallen.';

    public const WEEKEND_MESSAGE = 'Op zaterdag en zondag kunnen geen afspraken worden gepland.';

    public const SLOT_MESSAGE = 'Een afspraak begint en eindigt op het hele of halve uur (bijvoorbeeld 10:30-11:00).';

    /**
     * @param  string  $start  HH:MM:SS
     * @param  string  $end  HH:MM:SS
     */
    public static function fits(string $start, string $end): bool
    {
        return $start >= self::OPEN && $end <= self::CLOSE && $end > $start;
    }

    /**
     * @param  string  $date  YYYY-MM-DD (or anything Carbon can parse)
     */
    public static function isWorkday(string $date): bool
    {
        return CarbonImmutable::parse($date)->isWeekday();
    }

    /**
     * Whether a time is on the hour or half hour, e.g. 10:00 or 10:30.
     */
    public static function onSlot(string $time): bool
    {
        $moment = CarbonImmutable::parse($time);

        return $moment->second === 0 && $moment->minute % self::SLOT_MINUTES === 0;
    }

    /**
     * End time for a treatment, rounded up to the next half hour so the next appointment can start on a slot.
     *
     * @param  string  $start  HH:MM:SS
     * @return string HH:MM:SS
     */
    public static function endFor(string $start, int $durationMinutes): string
    {
        $slots = (int) ceil(max($durationMinutes, 1) / self::SLOT_MINUTES);

        return CarbonImmutable::parse($start)->addMinutes($slots * self::SLOT_MINUTES)->format('H:i:s');
    }
}
