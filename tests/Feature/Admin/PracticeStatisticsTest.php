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
    // Feedback belongs to an appointment that took place, here last week so today's numbers stay the same
    $review = function (User $dentist, bool $satisfied, ?string $comment = null) use ($customer) {
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'dentist_id' => $dentist->id,
            'date' => '2026-10-12',
            'starttime' => '09:00:00',
            'endtime' => '09:30:00',
            'status' => 'completed',
        ]);
        Feedback::create([
            'appointment_id' => $appointment->appointment_id,
            'customer_id' => $customer->id,
            'dentist_id' => $dentist->id,
            'satisfied' => $satisfied,
            'comment' => $comment,
        ]);
    };
    $review($this->dentist, true, 'Top');
    $review($this->dentist, true);
    $review($this->otherDentist, false);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            // satisfaction
            ->where('statistics.satisfaction.count', 3)
            ->where('statistics.satisfaction.satisfied', 2)
            ->where('statistics.satisfaction.percentage', 67)
            // rooms in use right now
            ->where('statistics.rooms.total', 3)
            ->where('statistics.rooms.inUse', 1)
            ->where('statistics.rooms.list.0.name', 'Kamer 1')
            ->whereNot('statistics.rooms.list.0.appointment', null)
            ->where('statistics.rooms.list.1.appointment', null)
            // customers at the practice
            ->where('statistics.customers.presentNow', 1)
            ->where('statistics.customers.expectedLater', 1)
            ->where('statistics.customers.today', 3)
            // employees at work today: both dentists and the assistant, two of them busy now
            ->where('statistics.employees.atWork', 3)
            ->where('statistics.employees.busyNow', 2)
            ->where('statistics.employees.total', 4)
            // feedback per dentist, most satisfied first
            ->where('statistics.dentistFeedback.0.id', $this->dentist->id)
            ->where('statistics.dentistFeedback.0.percentage', 100)
            ->where('statistics.dentistFeedback.1.percentage', 0)
            ->has('statistics.latestFeedback', 3));
});

test('satisfaction is empty when there is no feedback yet', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('statistics.satisfaction.percentage', null)
            ->where('statistics.satisfaction.count', 0)
            ->where('statistics.dentistFeedback.0.percentage', null));
});

test('other users cannot open the admin dashboard', function () {
    $this->actingAs($this->dentist)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('the admin dashboard lists today\'s appointments', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->has('today', 3)); // the three appointments on 19 Oct
});
