<?php

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-10-19 10:15:00');

    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->otherDentist = User::factory()->role(Role::DENTIST)->create();
    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();
    $this->rooms = collect(['Kamer 1', 'Kamer 2', 'Kamer 3'])->map(fn ($name) => Room::create(['name' => $name]));

    $appointment = fn (array $attributes) => Appointment::create([
        'customer_id' => User::factory()->create()->id,
        'dentist_id' => $this->dentist->id,
        'date' => '2026-10-19',
        'status' => 'scheduled',
        'note' => '',
        ...$attributes,
    ]);

    // Running now, with an assistant
    $appointment(['room_id' => $this->rooms[0]->room_id, 'assistant_id' => $this->assistant->id, 'starttime' => '10:00:00', 'endtime' => '10:30:00']);
    // Later today with the other dentist
    $appointment(['room_id' => $this->rooms[1]->room_id, 'dentist_id' => $this->otherDentist->id, 'starttime' => '11:00:00', 'endtime' => '11:30:00']);
    // Already finished
    $appointment(['room_id' => $this->rooms[1]->room_id, 'starttime' => '08:00:00', 'endtime' => '08:30:00']);
    // Tomorrow, should not count
    $appointment(['date' => '2026-10-20', 'starttime' => '10:00:00', 'endtime' => '10:30:00']);

    $this->admin = User::factory()->admin()->create();
});

afterEach(fn () => Carbon::setTestNow());

test('admins see the practice statistics on the dashboard', function () {
    $customer = User::factory()->create();
    Feedback::create(['customer_id' => $customer->id, 'dentist_id' => $this->dentist->id, 'rating' => 5, 'comment' => 'Top']);
    Feedback::create(['customer_id' => $customer->id, 'dentist_id' => $this->dentist->id, 'rating' => 4]);
    Feedback::create(['customer_id' => $customer->id, 'dentist_id' => $this->otherDentist->id, 'rating' => 2]);

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            // satisfaction
            ->where('Statistics.satisfaction.average', 3.7)
            ->where('Statistics.satisfaction.count', 3)
            ->where('Statistics.satisfaction.positivePercentage', 67)
            // rooms in use right now
            ->where('Statistics.rooms.total', 3)
            ->where('Statistics.rooms.inUse', 1)
            ->where('Statistics.rooms.list.0.name', 'Kamer 1')
            ->whereNot('Statistics.rooms.list.0.appointment', null)
            ->where('Statistics.rooms.list.1.appointment', null)
            // customers at the practice
            ->where('Statistics.customers.presentNow', 1)
            ->where('Statistics.customers.expectedLater', 1)
            ->where('Statistics.customers.today', 3)
            // employees at work today: both dentists and the assistant, two of them busy now
            ->where('Statistics.employees.atWork', 3)
            ->where('Statistics.employees.busyNow', 2)
            ->where('Statistics.employees.total', 4)
            // feedback per dentist, best rated first
            ->where('Statistics.dentistFeedback.0.id', $this->dentist->id)
            ->where('Statistics.dentistFeedback.0.average', 4.5)
            ->where('Statistics.dentistFeedback.1.average', 2)
            ->has('Statistics.latestFeedback', 3));
});

test('satisfaction is empty when there is no feedback yet', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('Statistics.satisfaction.average', null)
            ->where('Statistics.satisfaction.count', 0)
            ->where('Statistics.dentistFeedback.0.average', null));
});

test('other users do not get the practice statistics', function () {
    $this->actingAs($this->dentist)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('Statistics', null));
});
