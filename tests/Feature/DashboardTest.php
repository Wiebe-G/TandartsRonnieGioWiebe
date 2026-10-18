<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;

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

test('the dashboard only gets your own upcoming appointments, in order', function () {
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

    foreach ([$customer, $dentist] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('Appointments', 2)
                ->where('Appointments.0.starttime', '11:00:00')
                ->where('Appointments.1.starttime', '14:00:00'));
    }

    Carbon::setTestNow();
});
