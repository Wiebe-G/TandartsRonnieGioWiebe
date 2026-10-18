<?php

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
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
