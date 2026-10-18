<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();
    $this->customer = User::factory()->create(['employee_id' => $this->dentist->id]);
    $this->room = Room::create(['name' => 'Kamer 1']);
    $this->treatment = Treatment::create(['name' => 'Controle', 'price' => 28.5, 'duration' => 30, 'active' => true]);

    // Wednesday 21 October 2026
    $this->payload = fn (array $attributes = []) => [
        'customer_id' => $this->customer->id,
        'treatment_id' => $this->treatment->treatment_id,
        'dentist_id' => $this->dentist->id,
        'assistant_id' => $this->assistant->id,
        'room_id' => $this->room->room_id,
        'date' => '2026-10-21',
        'starttime' => '09:00',
        'note' => '',
        ...$attributes,
    ];

    $this->plan = fn (array $attributes = []) => $this->actingAs($this->admin)
        ->post(route('admin.appointments.store'), ($this->payload)($attributes));
});

test('admins see all appointments on the overview', function () {
    ($this->plan)();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/appointments/index')
            ->has('appointments', 1)
            ->where('appointments.0.room.name', 'Kamer 1'));
});

test('the create page offers active treatments, staff, rooms and the taken times of a day', function () {
    Treatment::create(['name' => 'Oud', 'price' => 10, 'duration' => 30, 'active' => false]);
    ($this->plan)();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.create', ['date' => '2026-10-21']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/appointments/create')
            ->has('customers', 1)
            ->has('treatments', 1)
            ->has('options.dentists', 1)
            ->has('options.assistants', 1)
            ->has('options.rooms', 1)
            ->has('busy', 1)
            ->where('busy.0.starttime', '09:00:00'));
});

test('admins can plan an appointment', function () {
    ($this->plan)(['note' => 'Eerste controle'])->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();
    expect($appointment->starttime)->toBe('09:00:00')
        ->and($appointment->endtime)->toBe('09:30:00')
        ->and($appointment->status)->toBe('scheduled')
        ->and($appointment->room_id)->toBe($this->room->room_id)
        ->and(DB::table('appointment_treatment')->where('appointment_id', $appointment->appointment_id)->value('treatment_id'))
        ->toBe($this->treatment->treatment_id);
});

test('planning redirects to the new appointment', function () {
    ($this->plan)()->assertRedirect(route('admin.appointments.show', Appointment::query()->sole()));
});

test('everything except the note is required', function () {
    ($this->plan)([
        'customer_id' => null, 'treatment_id' => null, 'dentist_id' => null,
        'assistant_id' => null, 'room_id' => null, 'starttime' => null,
    ])->assertSessionHasErrors(['customer_id', 'treatment_id', 'dentist_id', 'assistant_id', 'room_id', 'starttime']);
});

test('roles must match', function () {
    ($this->plan)(['dentist_id' => $this->assistant->id])->assertSessionHasErrors('dentist_id');
    ($this->plan)(['assistant_id' => $this->dentist->id])->assertSessionHasErrors('assistant_id');
    ($this->plan)(['customer_id' => $this->dentist->id])->assertSessionHasErrors('customer_id');
});

test('inactive treatments cannot be planned', function () {
    $this->treatment->update(['active' => false]);

    ($this->plan)()->assertSessionHasErrors('treatment_id');
});

test('nobody and no room can be booked twice', function () {
    ($this->plan)()->assertSessionHasNoErrors();
    $otherCustomer = User::factory()->create()->id;

    ($this->plan)(['customer_id' => $otherCustomer, 'assistant_id' => User::factory()->role(Role::ASSISTANT)->create()->id, 'room_id' => Room::create(['name' => 'Kamer 2'])->room_id])
        ->assertSessionHasErrors('starttime'); // same dentist

    ($this->plan)(['customer_id' => $otherCustomer, 'dentist_id' => User::factory()->role(Role::DENTIST)->create()->id, 'room_id' => Room::create(['name' => 'Kamer 3'])->room_id])
        ->assertSessionHasErrors('starttime'); // same assistant

    ($this->plan)(['customer_id' => $otherCustomer, 'dentist_id' => User::factory()->role(Role::DENTIST)->create()->id, 'assistant_id' => User::factory()->role(Role::ASSISTANT)->create()->id])
        ->assertSessionHasErrors('starttime'); // same room

    expect(Appointment::query()->count())->toBe(1);
});

test('back to back appointments are fine', function () {
    ($this->plan)();
    ($this->plan)(['starttime' => '09:30'])->assertSessionHasNoErrors();

    expect(Appointment::query()->count())->toBe(2);
});

test('appointments are planned on weekdays between 9:00 and 17:00 on the half hour', function (array $attributes, string $error) {
    ($this->plan)($attributes)->assertSessionHasErrors($error);

    expect(Appointment::query()->count())->toBe(0);
})->with([
    'saturday' => [['date' => '2026-10-24'], 'date'],
    'sunday' => [['date' => '2026-10-25'], 'date'],
    'before opening' => [['starttime' => '08:30'], 'starttime'],
    'ends after closing' => [['starttime' => '17:00'], 'starttime'],
    'quarter past' => [['starttime' => '10:15'], 'starttime'],
]);

test('the end time is rounded up to the next half hour', function (int $duration, string $end) {
    $this->treatment->update(['duration' => $duration]);

    ($this->plan)(['starttime' => '10:30'])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->endtime)->toBe($end);
})->with([
    '15 minutes' => [15, '11:00:00'],
    '45 minutes' => [45, '11:30:00'],
    '90 minutes' => [90, '12:00:00'],
]);

test('the last appointment may end exactly at 17:00', function () {
    ($this->plan)(['starttime' => '16:30'])->assertSessionHasNoErrors();
});

test('admins can open an appointment', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.show', $appointment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/appointments/show')
            ->where('appointment.appointment_id', $appointment->appointment_id)
            ->where('appointment.treatments.0.duration', 30)
            ->has('rooms', 1)
            ->has('statuses', 4));
});

test('admins can reschedule an appointment and change its status', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();

    $this->actingAs($this->admin)
        ->put(route('admin.appointments.update', $appointment), [
            'date' => '2026-10-22',
            'starttime' => '13:00',
            'status' => 'completed',
            'note' => 'Verzet',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.appointments.show', $appointment));

    $appointment->refresh();
    expect($appointment->date->toDateString())->toBe('2026-10-22')
        ->and($appointment->starttime)->toBe('13:00:00')
        ->and($appointment->endtime)->toBe('13:30:00')
        ->and($appointment->status)->toBe('completed')
        ->and($appointment->note)->toBe('Verzet');
});

test('rescheduling follows the planning rules', function () {
    ($this->plan)();
    ($this->plan)(['customer_id' => User::factory()->create()->id, 'starttime' => '11:00']);
    $second = Appointment::query()->where('starttime', '11:00:00')->sole();

    $move = fn (array $attributes) => $this->actingAs($this->admin)
        ->put(route('admin.appointments.update', $second), ['date' => '2026-10-21', 'status' => 'scheduled', ...$attributes]);

    $move(['starttime' => '09:00'])->assertSessionHasErrors('starttime'); // overlaps the first one
    $move(['starttime' => '10:10'])->assertSessionHasErrors('starttime');
    $move(['date' => '2026-10-24', 'starttime' => '10:00'])->assertSessionHasErrors('date');
    $move(['starttime' => '11:00'])->assertSessionHasNoErrors(); // keeping its own time is fine
});

test('admins can assign another room, but not one that is taken', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();
    $free = Room::create(['name' => 'Kamer 2']);
    $taken = Room::create(['name' => 'Kamer 3']);
    Appointment::create([
        'customer_id' => User::factory()->create()->id,
        'dentist_id' => User::factory()->role(Role::DENTIST)->create()->id,
        'room_id' => $taken->room_id,
        'date' => '2026-10-21',
        'starttime' => '09:00:00',
        'endtime' => '09:30:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.appointments.room', $appointment), ['room_id' => $taken->room_id])
        ->assertSessionHasErrors('room_id');

    $this->actingAs($this->admin)
        ->put(route('admin.appointments.room', $appointment), ['room_id' => null])
        ->assertSessionHasErrors('room_id');

    $this->actingAs($this->admin)
        ->put(route('admin.appointments.room', $appointment), ['room_id' => $free->room_id])
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->room_id)->toBe($free->room_id);
});

test('admins can delete an appointment', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();

    $this->actingAs($this->admin)
        ->delete(route('admin.appointments.destroy', $appointment))
        ->assertRedirect(route('admin.appointments.index'));

    expect(Appointment::query()->count())->toBe(0)
        ->and(DB::table('appointment_treatment')->count())->toBe(0);
});
