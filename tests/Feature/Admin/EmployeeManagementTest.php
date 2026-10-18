<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admins can list employees and filter them by role', function () {
    User::factory()->role(Role::DENTIST)->count(2)->create();
    User::factory()->role(Role::ASSISTANT)->create();
    User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/employees/index')
            ->has('employees.data', 4));

    $this->actingAs($this->admin)
        ->get(route('admin.employees.index', ['role' => Role::idFor(Role::DENTIST)]))
        ->assertInertia(fn ($page) => $page->has('employees.data', 2));
});

test('admins can create an employee', function () {
    $this->actingAs($this->admin)->get(route('admin.employees.create'))->assertOk();

    $response = $this->actingAs($this->admin)->post(route('admin.employees.store'), [
        'role_id' => Role::idFor(Role::DENTIST),
        'firstname' => 'Nieuwe',
        'lastname' => 'Tandarts',
        'email' => 'nieuwe@tandarts.nl',
        'status' => 'active',
        'password' => 'password',
    ]);

    $employee = User::query()->where('email', 'nieuwe@tandarts.nl')->firstOrFail();

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.employees.show', $employee));
    expect($employee->role->name)->toBe(Role::DENTIST);
});

test('an employee cannot be given the customer role', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.employees.store'), [
            'role_id' => Role::customerId(),
            'firstname' => 'Nieuwe',
            'lastname' => 'Medewerker',
            'email' => 'nieuwe@tandarts.nl',
            'status' => 'active',
            'password' => 'password',
        ])
        ->assertSessionHasErrors('role_id');
});

test('admins can view an employee', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();
    User::factory()->create(['employee_id' => $dentist->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.employees.show', $dentist))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/employees/show')
            ->where('employee.id', $dentist->id)
            ->has('customers', 1));
});

test('customers are not shown on the employee pages', function () {
    $customer = User::factory()->create();

    $this->actingAs($this->admin)->get(route('admin.employees.edit', $customer))->assertNotFound();
});

test('admins can update an employee', function () {
    $employee = User::factory()->role(Role::ASSISTANT)->create();

    $this->actingAs($this->admin)->get(route('admin.employees.edit', $employee))->assertOk();

    $this->actingAs($this->admin)
        ->put(route('admin.employees.update', $employee), [
            'role_id' => Role::idFor('dental_hygienist'),
            'firstname' => $employee->firstname,
            'lastname' => $employee->lastname,
            'email' => $employee->email,
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.employees.show', $employee));

    expect($employee->refresh()->role->name)->toBe('dental_hygienist');
});

test('admins cannot change their own role', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.employees.update', $this->admin), [
            'role_id' => Role::idFor(Role::DENTIST),
            'firstname' => $this->admin->firstname,
            'lastname' => $this->admin->lastname,
            'email' => $this->admin->email,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('role_id');

    expect($this->admin->refresh()->isAdmin())->toBeTrue();
});

test('admins can delete an employee and their customers are unassigned', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();
    $customer = User::factory()->create(['employee_id' => $dentist->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.employees.destroy', $dentist))
        ->assertRedirect(route('admin.employees.index'));

    expect(User::query()->find($dentist->id))->toBeNull()
        ->and($customer->refresh()->employee_id)->toBeNull();
});

test('admins cannot delete themselves', function () {
    $this->actingAs($this->admin)->delete(route('admin.employees.destroy', $this->admin));

    expect(User::query()->find($this->admin->id))->not->toBeNull();
});

test('a dentist with appointments cannot be deleted', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();
    Appointment::create([
        'customer_id' => User::factory()->create()->id,
        'dentist_id' => $dentist->id,
        'date' => today()->toDateString(),
        'starttime' => '09:00:00',
        'endtime' => '09:30:00',
        'status' => 'gepland',
        'note' => '',
    ]);

    $this->actingAs($this->admin)->delete(route('admin.employees.destroy', $dentist));

    expect(User::query()->find($dentist->id))->not->toBeNull();
});
