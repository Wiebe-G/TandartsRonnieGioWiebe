<?php

use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;

beforeEach(function () {
    $this->employee = User::factory()->role(Role::DENTIST)->create();
    $this->treatment = Treatment::create([
        'name' => 'Controle',
        'description' => 'Halfjaarlijkse controle',
        'price' => 28.50,
        'duration' => 30,
        'active' => true,
    ]);
});

test('a treatment can be created, the description is optional', function () {
    $this->actingAs($this->employee)
        ->post(route('treatments.store'), [
            'Name' => 'Vulling',
            'Description' => '',
            'Price' => 75,
            'DurationInMinutes' => 45,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('treatments'));

    expect(Treatment::query()->where('name', 'Vulling')->exists())->toBeTrue();
});

test('a treatment needs a sensible price and duration', function () {
    $this->actingAs($this->employee)
        ->post(route('treatments.store'), ['Name' => 'X', 'Price' => -5, 'DurationInMinutes' => 0])
        ->assertSessionHasErrors(['Price', 'DurationInMinutes']);
});

test('the edit page opens for a treatment', function () {
    $this->actingAs($this->employee)
        ->get(route('treatments.edit', $this->treatment->treatment_id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Treatments/Edit')
            ->where('Treatment.name', 'Controle'));
});

test('a treatment can be edited and made inactive', function () {
    $this->actingAs($this->employee)
        ->put(route('treatments.update', $this->treatment->treatment_id), [
            'Name' => 'Uitgebreide controle',
            'Description' => 'Met röntgenfoto',
            'Price' => 42.75,
            'DurationInMinutes' => 45,
            'Active' => false,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('treatments'));

    $this->treatment->refresh();
    expect($this->treatment->name)->toBe('Uitgebreide controle')
        ->and((float) $this->treatment->price)->toBe(42.75)
        ->and((bool) $this->treatment->active)->toBeFalse();
});

test('inactive treatments are not offered for new appointments', function () {
    $this->treatment->update(['active' => false]);

    $this->actingAs($this->employee)
        ->get(route('appointments.staff.create'))
        ->assertInertia(fn ($page) => $page->has('Treatments', 0));
});

test('customers cannot edit treatments', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('treatments.edit', $this->treatment->treatment_id))->assertForbidden();
    $this->actingAs($customer)->put(route('treatments.update', $this->treatment->treatment_id), [])->assertForbidden();
});
