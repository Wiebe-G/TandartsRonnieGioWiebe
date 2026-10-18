<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the homepage shows the welcome page with the opening hours', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('openingHours', ['open' => '09:00', 'close' => '17:00'])
        );

    $this->assertGuest();
});

test('logged-in users also just see the homepage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
