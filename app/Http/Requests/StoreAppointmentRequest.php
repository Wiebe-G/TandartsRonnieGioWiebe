<?php

namespace App\Http\Requests;

use App\Actions\Appointments\AvailableSlots;
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
            // Only the customer's own dentist; without an assigned dentist nothing is accepted
            'dentist_id' => ['required', 'integer', Rule::in([$this->user()->assignedDentist()?->id])],
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
            // The profile has to be complete first (the dentist has its own message on dentist_id)
            function (Validator $validator) {
                $missing = array_diff($this->user()->missingBookingDetails(), ['tandarts']);

                if ($missing !== []) {
                    $validator->errors()->add('profile', 'Vul eerst uw '.implode(' en ', $missing).' in bij Mijn profiel. Daarna kunt u een afspraak maken.');
                }
            },
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dentist_id.in' => $this->user()->assignedDentist()
                ? 'U kunt alleen een afspraak maken bij uw eigen tandarts.'
                : 'U heeft nog geen tandarts. Kies eerst een tandarts in uw profiel.',
        ];
    }
}
