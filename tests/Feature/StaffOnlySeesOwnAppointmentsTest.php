<?php

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->dentist = User::factory()->dentist()->create();
    $this->otherDentist = User::factory()->dentist()->create();
    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();

    $this->own = Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'assistant_id' => $this->assistant->id,
    ]);
    $this->other = Appointment::factory()->create(['dentist_id' => $this->otherDentist->id]);
});

test('dentists and assistants only see their own appointments in the overviews', function (string $person) {
    $user = $this->{$person};

    $this->actingAs($user)
        ->get(route('admin.appointments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments', 1)
            ->where('appointments.0.appointment_id', $this->own->appointment_id));

    $this->actingAs($user)
        ->get(route('appointments'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('Appointments', 1)
            ->where('Appointments.0.appointment_id', $this->own->appointment_id));

    $this->actingAs($user)
        ->get(route('appointments.index', ['maand' => $this->own->date->format('Y-m')]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments', fn ($appointments) => collect($appointments)->pluck('id')->doesntContain($this->other->appointment_id)));
})->with(['dentist', 'assistant']);

test('dentists cannot open or change an appointment of someone else', function () {
    $this->actingAs($this->dentist)->get(route('admin.appointments.show', $this->own))->assertOk();
    $this->actingAs($this->dentist)->get(route('admin.appointments.show', $this->other))->assertForbidden();
    $this->actingAs($this->dentist)->get(route('appointments.view', $this->other->appointment_id))->assertForbidden();

    $this->actingAs($this->dentist)
        ->patch(route('admin.appointments.complete', $this->other))
        ->assertForbidden();

    $this->actingAs($this->dentist)
        ->delete(route('admin.appointments.destroy', $this->other))
        ->assertForbidden();

    $this->actingAs($this->dentist)
        ->patch(route('appointments.cancel', $this->other))
        ->assertForbidden();

    expect(Appointment::find($this->other->appointment_id)->status)->toBe(Appointment::SCHEDULED);
});

test('assistants only see invoices for their own appointments', function () {
    $this->own->update(['status' => Appointment::COMPLETED]);
    $this->other->update(['status' => Appointment::COMPLETED]);
    $otherInvoice = Invoice::create([
        'appointment_id' => $this->other->appointment_id,
        'invoicedate' => today(),
        'expiration_date' => today()->addDays(Invoice::PAYMENT_DAYS),
        'amount' => '10.00',
    ]);

    $this->actingAs($this->assistant)
        ->get(route('admin.invoices.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('toInvoice', 1)
            ->where('toInvoice.0.appointment_id', $this->own->appointment_id)
            ->has('invoices', 0));

    $this->actingAs($this->assistant)->get(route('admin.invoices.show', $otherInvoice))->assertForbidden();
});

test('admins still see every appointment', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.appointments.index'))
        ->assertInertia(fn (Assert $page) => $page->has('appointments', 2));
});

test('an assistant who plans an appointment for a colleague goes back to the overview', function () {
    $colleague = User::factory()->role(Role::ASSISTANT)->create();
    $customer = User::factory()->withDentist($this->otherDentist)->create();
    $treatment = Treatment::create(['name' => 'Controle', 'price' => 28.5, 'duration' => 30, 'active' => true]);
    $room = Room::query()->first() ?? Room::create(['name' => 'Kamer 1']);
    $this->travelTo(Carbon::parse('2026-10-19 07:00'));

    $this->actingAs($this->assistant)
        ->post(route('admin.appointments.store'), [
            'customer_id' => $customer->id,
            'treatment_id' => $treatment->treatment_id,
            'assistant_id' => $colleague->id,
            'room_id' => $room->room_id,
            'date' => '2026-10-21',
            'starttime' => '11:00',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.appointments.index'));
});
