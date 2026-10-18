<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->otherDentist = User::factory()->role(Role::DENTIST)->create();
    $this->room = Room::create(['name' => 'Kamer 1']);
    $this->defaultRoom = Room::create(['name' => 'Kamer 2']);
    $this->defaultAssistant = User::factory()->role(Role::ASSISTANT)->create();

    $this->makeAppointment = fn (array $attributes = []) => Appointment::create([
        'customer_id' => User::factory()->create()->id,
        'dentist_id' => $this->dentist->id,
        'date' => '2026-10-21',
        'starttime' => '09:00:00',
        'endtime' => '09:30:00',
        'status' => 'scheduled',
        'note' => '',
        ...$attributes,
    ]);

    $this->validUpdate = fn (array $attributes = []) => [
        'date' => '2026-10-21',
        'starttime' => '09:00',
        'endtime' => '09:30',
        'dentist_id' => $this->dentist->id,
        'assistant_id' => $this->defaultAssistant->id,
        'room_id' => $this->defaultRoom->room_id,
        'status' => 'scheduled',
        'note' => '',
        ...$attributes,
    ];
});

test('an assistant and a room are required', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['assistant_id' => null, 'room_id' => null]))
        ->assertSessionHasErrors(['assistant_id', 'room_id']);
});

test('non-admins cannot open the calendar', function () {
    $this->actingAs($this->dentist)->get(route('admin.calendar.index'))->assertForbidden();
});

test('the calendar lists employees and no roster until one is picked', function () {
    User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.calendar.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/calendar')
            ->has('employees', 4) // admin, two dentists and an assistant, no customers
            ->where('selected', null)
            ->where('week.days', []));
});

test('admins can read the weekly roster of an employee', function () {
    ($this->makeAppointment)(['date' => '2026-10-21']); // Wednesday
    ($this->makeAppointment)(['date' => '2026-10-25', 'starttime' => '11:00:00', 'endtime' => '11:30:00']); // Sunday
    ($this->makeAppointment)(['date' => '2026-10-28']); // next week
    ($this->makeAppointment)(['dentist_id' => $this->otherDentist->id]); // someone else
    ($this->makeAppointment)(['dentist_id' => $this->otherDentist->id, 'assistant_id' => $this->dentist->id, 'starttime' => '10:00:00', 'endtime' => '10:30:00']);

    $this->actingAs($this->admin)
        ->get(route('admin.calendar.index', ['employee' => $this->dentist->id, 'week' => '2026-10-23']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.id', $this->dentist->id)
            ->where('week.start', '2026-10-19')
            ->where('week.previous', '2026-10-12')
            ->where('week.next', '2026-10-26')
            ->has('week.days', 7)
            ->where('week.days.0.date', '2026-10-19')
            ->has('week.days.2.appointments', 2) // own appointment + one as assistant
            ->has('week.days.6.appointments', 1)
            ->has('week.days.0.appointments', 0));
});

test('admins can update an appointment from the calendar', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)([
            'date' => '2026-10-22',
            'starttime' => '14:00',
            'endtime' => '15:00',
            'dentist_id' => $this->otherDentist->id,
            'room_id' => $this->room->room_id,
            'status' => 'completed',
            'note' => 'Verplaatst',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $appointment->refresh();
    expect($appointment->date->toDateString())->toBe('2026-10-22')
        ->and($appointment->starttime)->toBe('14:00:00')
        ->and($appointment->endtime)->toBe('15:00:00')
        ->and($appointment->dentist_id)->toBe($this->otherDentist->id)
        ->and($appointment->room_id)->toBe($this->room->room_id)
        ->and($appointment->status)->toBe('completed');
});

test('the end time must be after the start time', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '10:00', 'endtime' => '09:00']))
        ->assertSessionHasErrors('endtime');
});

test('a dentist cannot be double booked', function () {
    ($this->makeAppointment)(['starttime' => '10:00:00', 'endtime' => '10:30:00']);
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '10:00', 'endtime' => '10:30']))
        ->assertSessionHasErrors('starttime');
});

test('a room cannot be double booked', function () {
    ($this->makeAppointment)(['dentist_id' => $this->otherDentist->id, 'room_id' => $this->room->room_id]);
    $appointment = ($this->makeAppointment)(['starttime' => '11:00:00', 'endtime' => '11:30:00']);

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['room_id' => $this->room->room_id]))
        ->assertSessionHasErrors('room_id');
});

test('back to back appointments are allowed', function () {
    ($this->makeAppointment)(['starttime' => '09:30:00', 'endtime' => '10:00:00']);
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)())
        ->assertSessionHasNoErrors();
});

test('an assistant can be assigned from the calendar', function () {
    $assistant = User::factory()->role(Role::ASSISTANT)->create();
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['assistant_id' => $assistant->id]))
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->assistant_id)->toBe($assistant->id);

    $this->actingAs($this->admin)
        ->get(route('admin.calendar.index', ['employee' => $assistant->id, 'week' => '2026-10-21']))
        ->assertInertia(fn ($page) => $page
            ->has('options.assistants', 2)
            ->has('week.days.2.appointments', 1));
});

test('only assistants can be assigned as assistant', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['assistant_id' => $this->otherDentist->id]))
        ->assertSessionHasErrors('assistant_id');
});

test('an assistant who is already busy cannot be double booked', function () {
    $assistant = User::factory()->role(Role::ASSISTANT)->create();
    ($this->makeAppointment)(['dentist_id' => $this->otherDentist->id, 'assistant_id' => $assistant->id]);
    $appointment = ($this->makeAppointment)(['starttime' => '13:00:00', 'endtime' => '13:30:00']);

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['assistant_id' => $assistant->id]))
        ->assertSessionHasErrors('starttime');
});

test('the calendar only allows times between 9:00 and 17:00', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '16:30', 'endtime' => '17:30']))
        ->assertSessionHasErrors('starttime');

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '08:00', 'endtime' => '08:30']))
        ->assertSessionHasErrors('starttime');
});

test('the calendar refuses the weekend and times off the half hour', function () {
    $appointment = ($this->makeAppointment)();

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['date' => '2026-10-25']))
        ->assertSessionHasErrors('date');

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '09:00', 'endtime' => '09:45']))
        ->assertSessionHasErrors('starttime');

    $this->actingAs($this->admin)
        ->put(route('admin.calendar.update', $appointment), ($this->validUpdate)(['starttime' => '09:30', 'endtime' => '10:30']))
        ->assertSessionHasNoErrors();
});
