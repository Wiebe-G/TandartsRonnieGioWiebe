<?php

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-19 07:00'));
    Notification::fake();

    $this->dentist = User::factory()->dentist()->create();
});

test('customers without a dentist or address details see what is missing', function () {
    $customer = User::factory()->create(['phonenumber' => '06 12345678']);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page->where('missing', ['tandarts', 'adres', 'geboortedatum']));

    $this->actingAs($customer)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('missingBookingDetails', ['tandarts', 'adres', 'geboortedatum'])
        );
});

test('a customer with incomplete details cannot book, even by posting directly', function () {
    $treatment = Treatment::factory()->create(['duration' => 30]);
    $customer = User::factory()->readyToBook($this->dentist)->create(['address' => null]);

    $this->actingAs($customer)
        ->post(route('appointments.store'), [
            'treatments' => [$treatment->treatment_id],
            'dentist_id' => $this->dentist->id,
            'date' => '2026-10-20',
            'starttime' => '09:00',
        ])
        ->assertSessionHasErrors('profile');

    expect(Appointment::count())->toBe(0);
});

test('a customer with complete details can open the booking form', function () {
    $customer = User::factory()->readyToBook($this->dentist)->create();

    expect($customer->missingBookingDetails())->toBe([]);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertOk();

    $this->actingAs($customer)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('missingBookingDetails', []));
});

test('the booking form gets the details for the example invoice', function () {
    $customer = User::factory()->readyToBook($this->dentist)->create();

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('invoiceCustomer.name', $customer->name)
            ->where('invoiceCustomer.email', $customer->email)
            ->where('invoiceCustomer.address.street', 'Dorpsstraat '.$customer->postcode->house_number)
            ->where('paymentDays', 14));
});
