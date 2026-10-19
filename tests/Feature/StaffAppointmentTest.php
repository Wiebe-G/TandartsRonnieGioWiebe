<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->dentist = User::factory()->role(Role::DENTIST)->create();
    $this->assistant = User::factory()->role(Role::ASSISTANT)->create();
    $this->customer = User::factory()->create();
    $this->treatment = Treatment::create(['name' => 'Controle', 'price' => 28.5, 'duration' => 30, 'active' => true]);

    // Wednesday 21 October 2026, the same fields the page sends
    $this->payload = fn (array $attributes = []) => [
        'Customer_id' => $this->customer->id,
        'Dentist_id' => $this->dentist->id,
        'Assistant_id' => $this->assistant->id,
        'Date' => '2026-10-21 09:00:00',
        'Type' => $this->treatment->treatment_id,
        'Note' => 'Controle',
        ...$attributes,
    ];

    $this->plan = fn (array $attributes = []) => $this->actingAs($this->assistant)
        ->post(route('appointments.staff.store'), ($this->payload)($attributes));
});

test('the create page offers dentists and assistants with their role', function () {
    $this->actingAs($this->assistant)
        ->get(route('appointments.staff.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('Customers', 1)
            ->where('Users', fn ($users) => collect($users)->contains(
                fn ($user) => $user['id'] === $this->assistant->id && $user['role']['name'] === Role::ASSISTANT,
            )));
});

test('an appointment can be made', function () {
    ($this->plan)()->assertSessionHasNoErrors()->assertRedirect(route('appointments'));

    $appointment = Appointment::query()->sole();
    expect($appointment->assistant_id)->toBe($this->assistant->id)
        ->and($appointment->status)->toBe('scheduled')
        ->and($appointment->endtime)->toBe('09:30:00');
});

test('the assistant and the note are optional', function () {
    ($this->plan)(['Assistant_id' => null, 'Note' => ''])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->assistant_id)->toBeNull();
});

test('roles must match', function () {
    ($this->plan)(['Dentist_id' => $this->assistant->id])->assertSessionHasErrors('Dentist_id');
    ($this->plan)(['Assistant_id' => $this->customer->id])->assertSessionHasErrors('Assistant_id');
    ($this->plan)(['Customer_id' => $this->dentist->id])->assertSessionHasErrors('Customer_id');
});

test('nobody can be booked twice, but back to back is fine', function () {
    ($this->plan)();

    ($this->plan)(['Customer_id' => User::factory()->create()->id, 'Assistant_id' => null])
        ->assertSessionHasErrors('Dentist_id');
    ($this->plan)(['Customer_id' => User::factory()->create()->id, 'Dentist_id' => User::factory()->role(Role::DENTIST)->create()->id])
        ->assertSessionHasErrors('Assistant_id');
    ($this->plan)(['Customer_id' => User::factory()->create()->id, 'Date' => '2026-10-21 09:30:00'])
        ->assertSessionHasNoErrors();

    expect(Appointment::query()->count())->toBe(2);
});

test('appointments are planned on weekdays between 9:00 and 17:00 on the half hour', function (string $date) {
    ($this->plan)(['Date' => $date])->assertSessionHasErrors('Date');

    expect(Appointment::query()->count())->toBe(0);
})->with([
    'saturday' => '2026-10-24 10:00:00',
    'before opening' => '2026-10-21 08:30:00',
    'ends after closing' => '2026-10-21 16:45:00',
    'quarter past' => '2026-10-21 10:15:00',
]);

test('the end time is rounded up to the next half hour', function () {
    $this->treatment->update(['duration' => 45]);

    ($this->plan)(['Date' => '2026-10-21 10:30:00'])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->endtime)->toBe('11:30:00');
});

test('an appointment without an assistant can be opened', function () {
    ($this->plan)(['Assistant_id' => null]);

    $this->actingAs($this->assistant)
        ->get(route('appointments.view', Appointment::query()->sole()->appointment_id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('Appointment.assistant', null));
});

test('an appointment can be moved and keeps its status', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();
    $appointment->update(['status' => 'no_show']);

    $this->actingAs($this->assistant)
        ->post(route('appointments.update'), [
            'Appointment_Id' => $appointment->appointment_id,
            'Note' => '',
            'Date' => '2026-10-22',
            'Starttime' => '13:00',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('appointments'));

    $appointment->refresh();
    expect($appointment->starttime)->toBe('13:00:00')
        ->and($appointment->endtime)->toBe('13:30:00')
        ->and($appointment->status)->toBe('no_show');
});

test('moving follows the planning rules', function () {
    ($this->plan)();
    ($this->plan)(['Customer_id' => User::factory()->create()->id, 'Date' => '2026-10-21 11:00:00']);
    $second = Appointment::query()->where('starttime', '11:00:00')->sole();

    $move = fn (string $date, string $start) => $this->actingAs($this->assistant)->post(route('appointments.update'), [
        'Appointment_Id' => $second->appointment_id,
        'Note' => '',
        'Date' => $date,
        'Starttime' => $start,
    ]);

    $move('2026-10-21', '09:00')->assertSessionHasErrors('Dentist_id'); // overlaps the first one
    $move('2026-10-24', '10:00')->assertSessionHasErrors('Date');
    $move('2026-10-21', '10:10')->assertSessionHasErrors('Date');
    $move('2026-10-21', '11:00:00')->assertSessionHasNoErrors(); // the page sends HH:MM:SS
});

test('an appointment can be deleted', function () {
    ($this->plan)();
    $appointment = Appointment::query()->sole();

    $this->actingAs($this->assistant)
        ->post(route('appointments.delete'), ['id' => $appointment->appointment_id])
        ->assertRedirect(route('appointments'));

    expect(Appointment::query()->count())->toBe(0)
        ->and(DB::table('appointment_treatment')->count())->toBe(0);
});
