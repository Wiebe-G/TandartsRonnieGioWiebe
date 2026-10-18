<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Within the opening hours: weekdays, half-hour blocks between 9:00 and 17:00
        $hour = fake()->numberBetween(9, 16);

        return [
            'customer_id' => User::factory(),
            'dentist_id' => User::factory()->dentist(),
            'assistant_id' => null,
            'date' => $this->weekday(fake()->dateTimeBetween('+1 day', '+2 months')),
            'starttime' => sprintf('%02d:00', $hour),
            'endtime' => sprintf('%02d:30', $hour),
            'status' => Appointment::SCHEDULED,
            'note' => null,
        ];
    }

    /**
     * An appointment that already took place.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->weekday(fake()->dateTimeBetween('-1 year', '-3 days')),
            'status' => Appointment::COMPLETED,
        ]);
    }

    /**
     * The same day, or the Monday after when it falls in the weekend.
     */
    private function weekday(\DateTimeInterface $date): string
    {
        $day = CarbonImmutable::instance($date);

        return ($day->isWeekend() ? $day->next(CarbonImmutable::MONDAY) : $day)->format('Y-m-d');
    }
}
