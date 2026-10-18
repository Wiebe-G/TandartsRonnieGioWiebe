<?php

use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
});

test('the home page never logs anyone in', function () {
    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('welcome'));

    $this->assertGuest();
});

test('admins can view the app as a customer and switch back', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.customers.impersonate', $this->customer))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->customer);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('customer/dashboard')
            ->where('auth.impersonating', true)
            ->where('auth.user.id', $this->customer->id));

    $this->post(route('impersonation.stop'))
        ->assertRedirect(route('admin.customers.show', $this->customer));

    $this->assertAuthenticatedAs($this->admin);

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.impersonating', false));
});

test('only customers can be viewed this way', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.customers.impersonate', $dentist))
        ->assertNotFound();

    $this->assertAuthenticatedAs($this->admin);
});

test('only admins can start it', function () {
    $dentist = User::factory()->role(Role::DENTIST)->create();

    $this->actingAs($dentist)
        ->post(route('admin.customers.impersonate', $this->customer))
        ->assertForbidden();

    $this->assertAuthenticatedAs($dentist);
});

test('switching back does nothing for someone who did not start as an admin', function () {
    $this->actingAs($this->customer)
        ->post(route('impersonation.stop'))
        ->assertForbidden();

    $this->assertAuthenticatedAs($this->customer);
});
