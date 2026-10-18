<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admins can list and search customers', function () {
    User::factory()->create(['firstname' => 'Jan', 'lastname' => 'Klant']);
    User::factory()->create(['firstname' => 'Piet', 'lastname' => 'Patiënt']);
    User::factory()->role(Role::DENTIST)->create(['firstname' => 'Jan', 'lastname' => 'Tandarts']);

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['search' => 'Jan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers.data', 1)
            ->where('customers.data.0.lastname', 'Klant'));
});

test('admins can create a customer', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($this->admin)->get(route('admin.customers.create'))->assertOk();

    $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), [
        'firstname' => 'Nieuwe',
        'lastname' => 'Klant',
        'email' => 'nieuwe@klant.nl',
        'phonenumber' => '0612345678',
        'birthday' => '1990-05-01',
        'status' => 'active',
        'employee_id' => $dentist->id,
        'password' => 'password',
    ]);

    $customer = User::query()->where('email', 'nieuwe@klant.nl')->firstOrFail();

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.customers.show', $customer));
    expect($customer->isCustomer())->toBeTrue()
        ->and($customer->employee_id)->toBe($dentist->id)
        ->and(Hash::check('password', $customer->password))->toBeTrue();
});

test('a customer can only be linked to a dentist', function () {
    $assistant = User::factory()->role('assistant')->create();

    $this->actingAs($this->admin)
        ->post(route('admin.customers.store'), [
            'firstname' => 'Nieuwe',
            'lastname' => 'Klant',
            'email' => 'nieuwe@klant.nl',
            'status' => 'active',
            'employee_id' => $assistant->id,
            'password' => 'password',
        ])
        ->assertSessionHasErrors('employee_id');
});

test('admins can view a customer', function () {
    $customer = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/show')
            ->where('customer.id', $customer->id));
});

test('employees are not shown on the customer pages', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($this->admin)->get(route('admin.customers.show', $dentist))->assertNotFound();
});

test('admins can update a customer and keep the password when it is left empty', function () {
    $customer = User::factory()->create();
    $oldPassword = $customer->password;

    $this->actingAs($this->admin)->get(route('admin.customers.edit', $customer))->assertOk();

    $this->actingAs($this->admin)
        ->put(route('admin.customers.update', $customer), [
            'firstname' => 'Gewijzigd',
            'lastname' => $customer->lastname,
            'email' => $customer->email,
            'status' => 'inactive',
            'password' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.customers.show', $customer));

    $customer->refresh();
    expect($customer->firstname)->toBe('Gewijzigd')
        ->and($customer->status)->toBe('inactive')
        ->and($customer->password)->toBe($oldPassword);
});

test('admins can delete a customer', function () {
    $customer = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.customers.destroy', $customer))
        ->assertRedirect(route('admin.customers.index'));

    expect(User::query()->find($customer->id))->toBeNull();
});

test('a customer with appointments cannot be deleted', function () {
    $customer = User::factory()->create();
    Appointment::create([
        'customer_id' => $customer->id,
        'dentist_id' => User::factory()->role(Role::DENTIST)->create()->id,
        'date' => today()->toDateString(),
        'starttime' => '09:00:00',
        'endtime' => '09:30:00',
        'status' => 'gepland',
        'note' => '',
    ]);

    $this->actingAs($this->admin)->delete(route('admin.customers.destroy', $customer));

    expect(User::query()->find($customer->id))->not->toBeNull();
});
