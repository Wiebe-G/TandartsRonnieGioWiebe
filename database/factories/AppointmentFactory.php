<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
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
        $hour = fake()->numberBetween(8, 16);

        return [
            'customer_id' => User::factory(),
            'dentist_id' => User::factory()->dentist(),
            'assistant_id' => null,
            'date' => fake()->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
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
            'date' => fake()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
            'status' => Appointment::COMPLETED,
        ]);
    }
}
