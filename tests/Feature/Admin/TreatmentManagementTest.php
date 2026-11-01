<?php

use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->treatment = Treatment::create([
        'name' => 'Controle',
        'description' => 'Halfjaarlijkse controle',
        'price' => 28.50,
        'duration' => 30,
        'active' => true,
    ]);
});

test('admins see all treatments', function () {
    Treatment::create(['name' => 'Oud', 'price' => 10, 'duration' => 15, 'active' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.treatments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/treatments/index')
            ->has('treatments', 2)
            ->where('treatments.0.name', 'Controle') // active ones first
            ->where('treatments.0.price', '28.50')
            ->where('treatments.0.active', true)
            ->where('treatments.0.appointments_count', 0));
});

test('admins can create a treatment', function () {
    $this->actingAs($this->admin)->get(route('admin.treatments.create'))->assertOk();

    $this->actingAs($this->admin)
        ->post(route('admin.treatments.store'), [
            'name' => 'Vulling',
            'description' => '',
            'price' => '75',
            'duration' => 45,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.treatments.index'));

    $treatment = Treatment::query()->where('name', 'Vulling')->sole();
    expect((float) $treatment->price)->toBe(75.0)
        ->and((bool) $treatment->active)->toBeTrue();
});

test('admins can edit a treatment and make it inactive', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.treatments.edit', $this->treatment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/treatments/edit')
            ->where('treatment.name', 'Controle')
            ->where('appointmentsCount', 0));

    $this->actingAs($this->admin)
        ->put(route('admin.treatments.update', $this->treatment), [
            'name' => 'Uitgebreide controle',
            'description' => 'Met röntgenfoto',
            'price' => '42.75',
            'duration' => 45,
            'active' => '0',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.treatments.index'));

    $this->treatment->refresh();
    expect($this->treatment->name)->toBe('Uitgebreide controle')
        ->and((float) $this->treatment->price)->toBe(42.75)
        ->and((int) $this->treatment->duration)->toBe(45)
        ->and((bool) $this->treatment->active)->toBeFalse();
});

test('a treatment needs a name, a price and a duration', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.treatments.update', $this->treatment), [
            'name' => '',
            'price' => '-5',
            'duration' => 0,
            'active' => '1',
        ])
        ->assertSessionHasErrors(['name', 'price', 'duration']);
});

test('only admins can change the price of a treatment', function (string $role) {
    $this->actingAs(User::factory()->role($role)->create())
        ->put(route('admin.treatments.update', $this->treatment), [
            'name' => 'Controle',
            'price' => '99',
            'duration' => 45,
            'active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.treatments.index'));

    $this->treatment->refresh();
    expect((float) $this->treatment->price)->toBe(28.5)
        ->and((int) $this->treatment->duration)->toBe(45);
})->with([Role::DENTIST, Role::ASSISTANT]);

test('other staff can manage treatments too, customers cannot', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($dentist)->get(route('admin.treatments.edit', $this->treatment))->assertOk();

    $customer = User::factory()->create();
    $this->actingAs($customer)->get(route('admin.treatments.edit', $this->treatment))->assertForbidden();
    $this->actingAs($customer)->put(route('admin.treatments.update', $this->treatment), [])->assertForbidden();
});
