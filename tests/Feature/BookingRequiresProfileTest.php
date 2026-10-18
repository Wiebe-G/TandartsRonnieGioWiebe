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

test('customers without a dentist or address details are sent to their profile first', function () {
    $customer = User::factory()->create(['phonenumber' => '06 12345678']);

    $this->actingAs($customer)
        ->get(route('appointments.create'))
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($customer)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('missingBookingDetails', ['een vaste tandarts', 'uw geboortedatum', 'uw adres'])
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
        ->assertRedirect(route('profile.edit'));

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
