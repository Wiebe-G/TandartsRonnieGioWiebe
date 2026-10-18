<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('customers see their own start screen with their appointments', function () {
    $dentist = User::factory()->dentist()->create();
    $customer = User::factory()->create(['employee_id' => $dentist->id]);

    $upcoming = Appointment::factory()
        ->for($customer, 'customer')
        ->for($dentist, 'dentist')
        ->create();
    $upcoming->treatments()->attach(Treatment::factory()->create(['name' => 'Controle']));

    Appointment::factory()->completed()->for($customer, 'customer')->for($dentist, 'dentist')->create();

    // Another customer's appointment must never show up.
    Appointment::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/dashboard')
            ->where('dentist.name', $dentist->name)
            ->has('upcoming', 1)
            ->where('upcoming.0.id', $upcoming->appointment_id)
            ->where('upcoming.0.treatments', ['Controle'])
            ->has('recent', 1)
        );
});

test('employees keep the regular dashboard', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('staff only get their own upcoming appointments on the dashboard, in order', function () {
    Carbon::setTestNow('2026-10-21 10:15:00');

    $dentist = User::factory()->role(Role::DENTIST)->create();
    $customer = User::factory()->create();
    $make = fn (array $attributes) => Appointment::create([
        'customer_id' => $customer->id,
        'dentist_id' => $dentist->id,
        'date' => '2026-10-21',
        'status' => 'scheduled',
        ...$attributes,
    ]);

    $make(['starttime' => '14:00:00', 'endtime' => '14:30:00']); // later today
    $make(['starttime' => '11:00:00', 'endtime' => '11:30:00']); // next one
    $make(['starttime' => '09:00:00', 'endtime' => '09:30:00']); // already over
    $make(['starttime' => '12:00:00', 'endtime' => '12:30:00', 'status' => 'cancelled']);
    // Someone else's appointment
    $make(['customer_id' => User::factory()->create()->id, 'dentist_id' => User::factory()->role(Role::DENTIST)->create()->id, 'starttime' => '10:30:00', 'endtime' => '11:00:00']);

    foreach ([$dentist] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('Appointments', 2)
                ->where('Appointments.0.starttime', '11:00:00')
                ->where('Appointments.1.starttime', '14:00:00'));
    }

    Carbon::setTestNow();
});
