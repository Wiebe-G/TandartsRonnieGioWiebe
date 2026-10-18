<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;

beforeEach(function () {
    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();
    $this->customer = User::factory()->create();
    $this->treatment = Treatment::create(['name' => 'Controle', 'price' => 28.5, 'duration' => 30, 'active' => true]);

    $this->payload = fn (array $attributes = []) => [
        'Customer_id' => $this->customer->id,
        'Dentist_id' => $this->dentist->id,
        'Assistant_id' => $this->assistant->id,
        'Date' => '2026-10-21 09:00:00',
        'Type' => $this->treatment->treatment_id,
        'Note' => 'Controle',
        ...$attributes,
    ];
});

test('the create page offers dentists and assistants with their role', function () {
    $this->actingAs($this->dentist)
        ->get(route('appointments.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('Users', fn ($users) => collect($users)->contains(fn ($user) => $user['id'] === $this->assistant->id && $user['role']['name'] === Role::ASSISTANT)));
});

test('an appointment can be made with an assistant', function () {
    $this->actingAs($this->dentist)
        ->post(route('appointments.store'), ($this->payload)())
        ->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();
    expect($appointment->assistant_id)->toBe($this->assistant->id)
        ->and($appointment->status)->toBe('scheduled');
});

test('an appointment can be made without an assistant', function () {
    $this->actingAs($this->dentist)
        ->post(route('appointments.store'), ($this->payload)(['Assistant_id' => null]))
        ->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->assistant_id)->toBeNull();
});

test('the assistant must have the assistant role', function () {
    $this->actingAs($this->dentist)
        ->post(route('appointments.store'), ($this->payload)(['Assistant_id' => $this->customer->id]))
        ->assertSessionHasErrors('Assistant_id');
});

test('the dentist must have the dentist role', function () {
    $this->actingAs($this->dentist)
        ->post(route('appointments.store'), ($this->payload)(['Dentist_id' => $this->assistant->id]))
        ->assertSessionHasErrors('Dentist_id');
});
