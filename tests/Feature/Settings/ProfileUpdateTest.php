<?php

use App\Models\Postcode;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->firstname)->toBe('Test');
    expect($user->lastname)->toBe('User');
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('contact details and address can be saved', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'phonenumber' => '06 12345678',
            'birthday' => '1990-05-17',
            'street' => 'Dorpsstraat',
            'house_number' => '12a',
            'postcode' => '1234ab',
            'city' => 'Utrecht',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->phonenumber)->toBe('06 12345678');
    expect($user->birthday->toDateString())->toBe('1990-05-17');
    expect($user->adress)->toBe('1234 AB 12a');
    expect($user->address->street)->toBe('Dorpsstraat');
    expect($user->address->city)->toBe('Utrecht');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('address.postcode', '1234 AB')
            ->where('address.house_number', '12a')
        );
});

test('customers can change their dentist', function () {
    $dentist = User::factory()->dentist()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'employee_id' => $dentist->id,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->employee_id)->toBe($dentist->id);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'employee_id' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->employee_id)->toBeNull();
});

test('an incomplete address is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'street' => 'Dorpsstraat',
        ])
        ->assertSessionHasErrors(['house_number', 'postcode', 'city']);

    expect(Postcode::count())->toBe(0);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
