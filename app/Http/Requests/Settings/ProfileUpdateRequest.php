<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'phonenumber' => $this->phoneRules(),
            'employee_id' => $this->dentistRules(),
            'birthday' => ['nullable', 'date', 'before:today'],

            // The address is optional, but once one part is filled in all parts are needed.
            'street' => ['nullable', 'required_with:house_number,postcode,city', 'string', 'max:255'],
            'house_number' => ['nullable', 'required_with:street,postcode,city', 'string', 'max:10'],
            'postcode' => ['nullable', 'required_with:street,house_number,city', 'string', 'regex:/^[1-9][0-9]{3}\s?[a-zA-Z]{2}$/'],
            'city' => ['nullable', 'required_with:street,house_number,postcode', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phonenumber' => 'telefoonnummer',
            'employee_id' => 'tandarts',
            'birthday' => 'geboortedatum',
            'street' => 'straat',
            'house_number' => 'huisnummer',
            'postcode' => 'postcode',
            'city' => 'plaats',
        ];
    }
}
