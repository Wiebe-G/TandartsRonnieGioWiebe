<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'phonenumber' => $this->phoneRules(required: true),
            'employee_id' => $this->dentistRules(),
            'password' => $this->passwordRules(),
        ], attributes: [
            'phonenumber' => 'telefoonnummer',
            'employee_id' => 'tandarts',
        ])->validate();

        // Anyone who registers themselves is a customer; employees are created by the practice.
        return User::create([
            'role_id' => Role::customerId(),
            'employee_id' => ($input['employee_id'] ?? null) ?: null,
            'firstname' => $input['firstname'],
            'lastname' => $input['lastname'],
            'email' => $input['email'],
            'phonenumber' => $input['phonenumber'],
            'password' => $input['password'],
        ]);
    }
}
