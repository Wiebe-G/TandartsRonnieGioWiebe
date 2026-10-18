<?php

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\Treatment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->dentist = User::factory()->dentist()->create();
    $this->customer = User::factory()->create(['employee_id' => $this->dentist->id]);
});

test('customers see the treatments they had, newest first', function () {
    $older = Appointment::factory()->completed()->for($this->customer, 'customer')->create(['date' => now()->subMonths(3)->toDateString()]);
    $assistant = User::factory()->assistant()->create();
    $newer = Appointment::factory()->completed()->for($this->customer, 'customer')->create([
        'date' => now()->subWeek()->toDateString(),
        'assistant_id' => $assistant->id,
    ]);
    $newer->treatments()->attach(Treatment::factory()->create(['name' => 'Vulling', 'price' => 85]));

    // Not part of the history: upcoming, cancelled, or someone else's.
    Appointment::factory()->for($this->customer, 'customer')->create();
    Appointment::factory()->completed()->for($this->customer, 'customer')->create(['status' => Appointment::CANCELLED]);
    Appointment::factory()->completed()->create();

    $this->actingAs($this->customer)
        ->get(route('treatments.history'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('treatments/history')
            ->has('appointments', 2)
            ->where('appointments.0.id', $newer->appointment_id)
            ->where('appointments.0.treatments.0.name', 'Vulling')
            ->where('appointments.0.treatments.0.price', '85.00')
            ->where('appointments.0.assistant', $assistant->name)
            ->where('appointments.1.id', $older->appointment_id)
            ->where('appointments.1.assistant', null)
        );
});

test('employees cannot open the treatment history page', function () {
    $this->actingAs($this->dentist)
        ->get(route('treatments.history'))
        ->assertForbidden();
});

test('customers can give and change feedback on an appointment that took place', function () {
    $appointment = Appointment::factory()->completed()->for($this->customer, 'customer')->create();

    $this->actingAs($this->customer)
        ->post(route('appointments.feedback', $appointment), ['satisfied' => false, 'comment' => 'Lang moeten wachten'])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->customer)
        ->post(route('appointments.feedback', $appointment), ['satisfied' => true, 'comment' => null])
        ->assertSessionHasNoErrors();

    $feedback = Feedback::sole();

    expect($feedback->appointment_id)->toBe($appointment->appointment_id);
    expect($feedback->customer_id)->toBe($this->customer->id);
    expect($feedback->satisfied)->toBeTrue();
    expect($feedback->comment)->toBeNull();

    $this->actingAs($this->customer)
        ->get(route('treatments.history'))
        ->assertInertia(fn (Assert $page) => $page->where('appointments.0.feedback.satisfied', true));
});

test('the customer has to choose satisfied or not satisfied', function () {
    $appointment = Appointment::factory()->completed()->for($this->customer, 'customer')->create();

    $this->actingAs($this->customer)
        ->post(route('appointments.feedback', $appointment), ['comment' => 'Alleen een notitie'])
        ->assertSessionHasErrors(['satisfied' => 'Kies of u tevreden of niet tevreden bent.']);

    expect(Feedback::count())->toBe(0);
});

test('feedback is not possible on upcoming or someone else\'s appointments', function () {
    $upcoming = Appointment::factory()->for($this->customer, 'customer')->create();
    $someoneElses = Appointment::factory()->completed()->create();

    $this->actingAs($this->customer)
        ->post(route('appointments.feedback', $upcoming), ['satisfied' => true])
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->post(route('appointments.feedback', $someoneElses), ['satisfied' => true])
        ->assertForbidden();

    expect(Feedback::count())->toBe(0);
});
