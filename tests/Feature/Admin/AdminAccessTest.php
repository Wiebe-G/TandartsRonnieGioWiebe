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
})->with([Role::CUSTOMER, Role::DENTIST, 'assistant', 'practice_manager']);

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

test('admins see the normal dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});
