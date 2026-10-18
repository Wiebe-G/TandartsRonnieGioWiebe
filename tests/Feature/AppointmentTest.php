<?php

use App\Models\Appointment;
use App\Models\Postcode;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // A fixed Monday morning keeps the free times predictable.
    $this->travelTo(Carbon::parse('2026-10-19 07:00'));
    Notification::fake();

    $this->dentist = User::factory()->dentist()->create();
    $this->customer = User::factory()->withAddress()->create(['employee_id' => $this->dentist->id]);
    $this->treatment = Treatment::factory()->create(['name' => 'Controle', 'duration' => 30]);
});

function bookingData(array $overrides = []): array
{
    return [
        'treatments' => [test()->treatment->treatment_id],
        'dentist_id' => test()->dentist->id,
        'date' => '2026-10-20',
        'starttime' => '09:00',
        'note' => 'Last van een kies',
        ...$overrides,
    ];
}

test('customers can book a free time', function () {
    $this->actingAs($this->customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('appointments.index', ['maand' => '2026-10']));

    $appointment = Appointment::sole();

    expect($appointment->customer_id)->toBe($this->customer->id);
    expect($appointment->dentist_id)->toBe($this->dentist->id);
    expect($appointment->date->toDateString())->toBe('2026-10-20');
    expect(substr($appointment->starttime, 0, 5))->toBe('09:00');
    expect(substr($appointment->endtime, 0, 5))->toBe('09:30');
    expect($appointment->treatments->pluck('name')->all())->toBe(['Controle']);

    Notification::assertSentTo($this->customer, AppointmentBooked::class, function (AppointmentBooked $notification) use ($appointment) {
        $mail = $notification->toMail($this->customer)->render();

        return $notification->appointment->is($appointment)
            && str_contains($mail, 'dinsdag 20 oktober 2026')
            && str_contains($mail, '09:00 – 09:30')
            && str_contains($mail, $this->dentist->name)
            && str_contains($mail, 'Controle');
    });
});

test('the booking form only offers the customer their own dentist', function () {
    User::factory()->dentist()->create(); // another dentist that must not show up

    $this->actingAs($this->customer)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('dentists', 1)
            ->where('dentists.0.id', $this->dentist->id)
            ->where('defaultDentist', $this->dentist->id)
            ->missing('alternatives')
        );
});

test('customers cannot book with another dentist', function () {
    $otherDentist = User::factory()->dentist()->create();

    $this->actingAs($this->customer)
        ->post(route('appointments.store'), bookingData(['dentist_id' => $otherDentist->id]))
        ->assertSessionHasErrors(['dentist_id' => 'U kunt alleen een afspraak maken bij uw eigen tandarts.']);

    expect(Appointment::count())->toBe(0);
});

test('a fully booked own dentist means no free times, not another dentist', function () {
    User::factory()->dentist()->create();
    Appointment::factory()->for($this->dentist, 'dentist')->create([
        'date' => '2026-10-20',
        'starttime' => '09:00',
        'endtime' => '17:00',
    ]);

    $this->actingAs($this->customer)
        ->get(route('appointments.create', [
            'treatments' => [$this->treatment->treatment_id],
            'dentist' => $this->dentist->id,
            'date' => '2026-10-20',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('slots', [])
            ->has('dentists', 1)
        );
});

test('customers without a dentist cannot book', function () {
    $customer = User::factory()->create(['employee_id' => null]);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dentists', 0)
            ->where('defaultDentist', null)
        );

    $this->actingAs($customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasErrors(['dentist_id' => 'U heeft nog geen tandarts. Kies eerst een tandarts in uw profiel.']);

    expect(Appointment::count())->toBe(0);
});

test('an assigned employee who is not a dentist does not count as a dentist', function () {
    $assistant = User::factory()->assistant()->create();
    $customer = User::factory()->create(['employee_id' => $assistant->id]);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page->has('dentists', 0));

    $this->actingAs($customer)
        ->post(route('appointments.store'), bookingData(['dentist_id' => $assistant->id]))
        ->assertSessionHasErrors('dentist_id');
});

test('a time that is already taken cannot be booked', function () {
    Appointment::factory()->for($this->dentist, 'dentist')->create([
        'date' => '2026-10-20',
        'starttime' => '08:45',
        'endtime' => '09:15',
    ]);

    $this->actingAs($this->customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasErrors('starttime');

    expect(Appointment::count())->toBe(1);
});

test('appointments cannot be booked in the weekend', function () {
    $this->actingAs($this->customer)
        ->post(route('appointments.store'), bookingData(['date' => '2026-10-24']))
        ->assertSessionHasErrors('starttime');
});

test('the booking form only offers free times', function () {
    Appointment::factory()->for($this->dentist, 'dentist')->create([
        'date' => '2026-10-20',
        'starttime' => '08:00',
        'endtime' => '09:00',
    ]);

    $this->actingAs($this->customer)
        ->get(route('appointments.create', [
            'treatments' => [$this->treatment->treatment_id],
            'dentist' => $this->dentist->id,
            'date' => '2026-10-20',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('afspraken/nieuw')
            ->where('defaultDentist', $this->dentist->id)
            ->where('duration', 30)
            ->where('slots.0', '09:00')
            ->where('slots', fn ($slots) => ! collect($slots)->contains('08:30') && collect($slots)->last() === '16:30')
        );
});

test('employees cannot use the booking form', function () {
    $this->actingAs($this->dentist)
        ->get(route('appointments.create'))
        ->assertForbidden();

    $this->actingAs($this->dentist)
        ->post(route('appointments.store'), bookingData())
        ->assertForbidden();
});

test('the agenda only shows the customer their own appointments', function () {
    Appointment::factory()->for($this->customer, 'customer')->for($this->dentist, 'dentist')->create(['date' => '2026-10-22']);
    Appointment::factory()->for($this->dentist, 'dentist')->create(['date' => '2026-10-23']);

    $this->actingAs($this->customer)
        ->get(route('appointments.index', ['maand' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('afspraken/index')
            ->where('month', '2026-10')
            ->has('appointments', 1)
            ->where('appointments.0.date', '2026-10-22')
        );

    $this->actingAs($this->dentist)
        ->get(route('appointments.index', ['maand' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page->has('appointments', 2));
});

test('customers can cancel their own upcoming appointment', function () {
    $appointment = Appointment::factory()->for($this->customer, 'customer')->create(['date' => '2026-10-22']);

    $this->actingAs($this->customer)
        ->patch(route('appointments.cancel', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->status)->toBe(Appointment::CANCELLED);
    Notification::assertSentTo($this->customer, AppointmentCancelled::class);
});

test('customers cannot cancel within 48 hours of the appointment', function () {
    // Now is Monday 07:00, so Wednesday 06:59 is just inside the 48 hours.
    $tooLate = Appointment::factory()->for($this->customer, 'customer')->create([
        'date' => '2026-10-21', 'starttime' => '06:59', 'endtime' => '07:29',
    ]);
    $justInTime = Appointment::factory()->for($this->customer, 'customer')->create([
        'date' => '2026-10-21', 'starttime' => '07:00', 'endtime' => '07:30',
    ]);

    $this->actingAs($this->customer)
        ->get(route('appointments.index', ['maand' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments.0.id', $tooLate->appointment_id)
            ->where('appointments.0.canCancel', false)
            ->where('appointments.0.cancelTooLate', true)
            ->where('appointments.1.canCancel', true)
        );

    $this->actingAs($this->customer)
        ->patch(route('appointments.cancel', $tooLate))
        ->assertRedirect();

    $this->actingAs($this->customer)
        ->patch(route('appointments.cancel', $justInTime))
        ->assertRedirect();

    expect($tooLate->refresh()->status)->toBe(Appointment::SCHEDULED);
    expect($justInTime->refresh()->status)->toBe(Appointment::CANCELLED);
    Notification::assertSentToTimes($this->customer, AppointmentCancelled::class, 1);
});

test('employees can still cancel an appointment within 48 hours', function () {
    $appointment = Appointment::factory()->for($this->customer, 'customer')->create([
        'date' => '2026-10-19', 'starttime' => '10:00', 'endtime' => '10:30',
    ]);

    $this->actingAs($this->dentist)
        ->patch(route('appointments.cancel', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->status)->toBe(Appointment::CANCELLED);
    Notification::assertSentTo($this->customer, AppointmentCancelled::class);
});

test('customers cannot cancel someone else\'s appointment', function () {
    $appointment = Appointment::factory()->create(['date' => '2026-10-22']);

    $this->actingAs($this->customer)
        ->patch(route('appointments.cancel', $appointment))
        ->assertForbidden();

    expect($appointment->refresh()->status)->toBe(Appointment::SCHEDULED);
});

test('the booking form lists what is missing from the profile', function () {
    $customer = User::factory()->create(['employee_id' => null, 'phonenumber' => null, 'address' => null]);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('missing', ['tandarts', 'adres', 'telefoonnummer']));

    $this->actingAs($this->customer)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page->where('missing', []));
});

test('customers without an address cannot book', function () {
    $customer = User::factory()->create(['employee_id' => $this->dentist->id, 'phonenumber' => '06 12345678', 'address' => null]);

    $this->actingAs($customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasErrors(['profile' => 'Vul eerst uw adres in bij Mijn profiel. Daarna kunt u een afspraak maken.']);

    expect(Appointment::count())->toBe(0);
});

test('customers without a phone number cannot book', function () {
    $customer = User::factory()->withAddress()->create(['employee_id' => $this->dentist->id]);
    $customer->update(['phonenumber' => null]);

    $this->actingAs($customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasErrors(['profile' => 'Vul eerst uw telefoonnummer in bij Mijn profiel. Daarna kunt u een afspraak maken.']);

    expect(Appointment::count())->toBe(0);
});

test('an address without street, house number or city does not count', function () {
    Postcode::create(['address' => '1234 AB 1', 'street' => '', 'house_number' => '1', 'residence' => '', 'city' => '']);
    $customer = User::factory()->create(['employee_id' => $this->dentist->id, 'phonenumber' => '06 12345678', 'address' => '1234 AB 1']);

    expect($customer->missingBookingDetails())->toBe(['adres']);
});

test('after filling in the profile the customer can book', function () {
    $customer = User::factory()->create(['employee_id' => $this->dentist->id, 'phonenumber' => null, 'address' => null]);

    $this->actingAs($customer)
        ->patch(route('profile.update'), [
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'email' => $customer->email,
            'phonenumber' => '06 12345678',
            'street' => 'Dorpsstraat',
            'house_number' => '12a',
            'postcode' => '1234 AB',
            'city' => 'Utrecht',
            'employee_id' => $this->dentist->id,
        ])
        ->assertSessionHasNoErrors();

    expect($customer->refresh()->missingBookingDetails())->toBe([]);

    $this->actingAs($customer)
        ->post(route('appointments.store'), bookingData())
        ->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(1);
});
