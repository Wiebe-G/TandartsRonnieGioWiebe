<?php

namespace App\Http\Requests;

use App\Actions\Appointments\AvailableSlots;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'treatments' => ['required', 'array', 'min:1'],
            'treatments.*' => ['integer', Rule::exists('treatment', 'treatment_id')->where('active', true)],
            'dentist_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role_id', Role::dentistId())],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starttime' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The chosen time must still be free when the form is submitted.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $slots = app(AvailableSlots::class)->for(
                    User::findOrFail($this->integer('dentist_id')),
                    $this->user(),
                    Carbon::parse($this->string('date')),
                    $this->duration(),
                );

                if (! in_array($this->string('starttime')->toString(), $slots, true)) {
                    $validator->errors()->add('starttime', 'Dit tijdstip is niet meer beschikbaar. Kies een ander tijdstip.');
                }
            },
        ];
    }

    /**
     * Length of the appointment in minutes, based on the chosen treatments.
     */
    public function duration(): int
    {
        return AvailableSlots::durationFor(Treatment::whereIn('treatment_id', $this->input('treatments', []))->get());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'treatments' => 'behandeling',
            'dentist_id' => 'tandarts',
            'date' => 'datum',
            'starttime' => 'tijdstip',
            'note' => 'opmerking',
        ];
    }
}
