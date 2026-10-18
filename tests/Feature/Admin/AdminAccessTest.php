<?php

use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

test('non-admins cannot open the admin pages', function (string $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.employees.index'))->assertForbidden();
})->with([Role::CUSTOMER, Role::DENTIST, Role::ASSISTANT, 'practice_manager']);

test('admins can open the user management page', function () {
    User::factory()->count(3)->create();
    User::factory()->role(Role::DENTIST)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/user-management')
            ->where('stats.customers', 3)
            ->where('stats.employees', 2)
            ->has('latestCustomers', 3));
});

test('admins land on their own dashboard after logging in', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('other users still land on the normal dashboard after logging in', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->post(route('login.store'), ['email' => $dentist->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('all staff share the appointment and treatment pages, customers do not', function (string $role) {
    $employee = User::factory()->role($role)->create();

    $this->actingAs($employee)->get(route('admin.appointments.index'))->assertOk();
    $this->actingAs($employee)->get(route('admin.treatments.index'))->assertOk();

    $customer = User::factory()->create();
    $this->actingAs($customer)->get(route('admin.appointments.index'))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.treatments.index'))->assertForbidden();
})->with([Role::DENTIST, Role::ASSISTANT, Role::ADMIN]);

test('only assistants and admins can make appointments', function (string $role, bool $allowed) {
    $employee = User::factory()->role($role)->create();

    $shared = $this->actingAs($employee)->get(route('admin.appointments.create'));
    $old = $this->actingAs($employee)->get(route('appointments.staff.create'));

    if ($allowed) {
        $shared->assertOk();
        $old->assertOk();
    } else {
        $shared->assertForbidden();
        $old->assertForbidden();
        $this->actingAs($employee)->post(route('admin.appointments.store'), [])->assertForbidden();
        $this->actingAs($employee)->post(route('appointments.staff.store'), [])->assertForbidden();
    }
})->with([
    'assistant' => [Role::ASSISTANT, true],
    'admin' => [Role::ADMIN, true],
    'dentist' => [Role::DENTIST, false],
]);

test('the rest of the admin pages stay admin only', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($dentist)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($dentist)->get(route('admin.calendar.index'))->assertForbidden();
});

test('staff who were sent to the appointment pages land there after logging in', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->get(route('admin.appointments.index'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $dentist->email, 'password' => 'password'])
        ->assertRedirect(route('admin.appointments.index'));
});

test('a remembered admin page is skipped when a non-admin logs in', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $dentist->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('a remembered staff page is skipped when a customer logs in', function () {
    $customer = User::factory()->create();

    $this->get(route('appointments'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('a remembered page the user may open is still used', function () {
    $admin = User::factory()->admin()->create();

    $this->get(route('admin.customers.index'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.customers.index'));
});
