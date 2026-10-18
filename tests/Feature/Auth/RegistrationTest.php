<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('a phone number is required when registering', function () {
    app()->setLocale('nl');

    $this->post(route('register.store'), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['phonenumber' => 'Telefoonnummer is verplicht.']);

    $this->assertGuest();
});

test('registering with an existing e-mail address gives a clear Dutch message', function () {
    app()->setLocale('nl');
    User::factory()->create(['email' => 'test@example.com']);

    $this->post(route('register.store'), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phonenumber' => '06 12345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['email' => 'Er bestaat al een account met dit e-mailadres. Log in of kies "Wachtwoord vergeten".']);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phonenumber' => '06 12345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->isCustomer())->toBeTrue();
    $response->assertRedirect(route('dashboard'));
});

test('new customers can choose their dentist when registering', function () {
    $dentist = User::factory()->dentist()->create();

    $this->get(route('register'))
        ->assertInertia(fn (Assert $page) => $page->where('dentists.0.id', $dentist->id));

    $this->post(route('register.store'), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phonenumber' => '06 12345678',
        'employee_id' => $dentist->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->employee_id)->toBe($dentist->id);
    expect($user->phonenumber)->toBe('06 12345678');
});

test('only dentists can be chosen when registering', function () {
    $notADentist = User::factory()->create();

    $this->post(route('register.store'), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phonenumber' => '06 12345678',
        'employee_id' => $notADentist->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('employee_id');

    $this->assertGuest();
});
