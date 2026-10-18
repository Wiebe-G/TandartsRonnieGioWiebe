<?php

namespace Database\Factories;

use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Controle', 'Gebitsreiniging', 'Vulling', 'Röntgenfoto', 'Wortelkanaalbehandeling']),
            'description' => null,
            'price' => fake()->randomFloat(2, 20, 250),
            'duration' => fake()->randomElement([15, 30, 45, 60]),
            'active' => true,
        ];
    }
}
