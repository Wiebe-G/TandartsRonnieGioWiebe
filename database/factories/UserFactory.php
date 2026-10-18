<?php

namespace Database\Factories;

use App\Models\Postcode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::customerId(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Give the user the role with the given name, e.g. Role::DENTIST.
     */
    public function role(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::idFor($name),
        ]);
    }

    public function admin(): static
    {
        return $this->role(Role::ADMIN);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a dentist working at the practice.
     */
    public function dentist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::dentistId(),
        ]);
    }

    /**
     * Indicate that the user is a dental assistant.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::idFor(Role::ASSISTANT),
        ]);
    }

    /**
     * A customer whose profile is complete enough to book: an address and a phone number.
     */
    public function withAddress(): static
    {
        return $this->state(function (array $attributes) {
            $houseNumber = (string) fake()->unique()->numberBetween(1, 9999);
            $key = Postcode::keyFor('1234AB', $houseNumber);

            Postcode::query()->firstOrCreate(['address' => $key], [
                'street' => 'Dorpsstraat',
                'house_number' => $houseNumber,
                'residence' => 'Utrecht',
                'city' => 'Utrecht',
            ]);

            return ['address' => $key, 'phonenumber' => '06 12345678'];
        });
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
