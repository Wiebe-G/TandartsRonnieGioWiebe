<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('only admins can open the log page', function () {
    $this->get(route('admin.logs.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->role(Role::ASSISTANT)->create())
        ->get(route('admin.logs.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/logs')->has('activity.data'));
});

test('creating, changing and deleting is logged with who did it', function () {
    $this->actingAs($this->admin);

    $treatment = Treatment::create(['name' => 'Controle', 'price' => 28.5, 'duration' => 30, 'active' => true]);
    $treatment->update(['price' => 30]);
    $treatment->delete();

    $logs = ActivityLog::query()->where('subject_type', 'Behandeling')->orderBy('id')->get();

    expect($logs->pluck('action')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($logs->pluck('user_id')->unique()->all())->toBe([$this->admin->id])
        ->and($logs[0]->description)->toBe('Behandeling Controle aangemaakt')
        ->and($logs[1]->changes)->toHaveKey('price');
});

test('passwords never end up in the log', function () {
    $customer = User::factory()->create();

    $customer->update(['password' => 'nieuw-wachtwoord']);

    expect(ActivityLog::query()->where('action', 'updated')->sole()->changes['password'])->toBe(['***', '***']);
});

test('logging in, also failed attempts, is logged', function () {
    $this->post(route('login.store'), ['email' => $this->admin->email, 'password' => 'fout']);
    $this->post(route('login.store'), ['email' => $this->admin->email, 'password' => 'password']);

    expect(ActivityLog::query()->where('action', 'login_failed')->sole()->description)
        ->toBe("Mislukte inlogpoging voor {$this->admin->email}")
        ->and(ActivityLog::query()->where('action', 'login')->sole()->user_id)->toBe($this->admin->id);
});

test('the activity can be searched and filtered', function () {
    ActivityLog::record('created', 'Klant Jan Jansen aangemaakt', 'Klant', 1, user: $this->admin);
    ActivityLog::record('deleted', 'Behandeling Bleken verwijderd', 'Behandeling', 2, user: $this->admin);

    $this->actingAs($this->admin)
        ->get(route('admin.logs.index', ['search' => 'Jansen']))
        ->assertInertia(fn ($page) => $page
            ->has('activity.data', 1)
            ->where('activity.data.0.description', 'Klant Jan Jansen aangemaakt'));

    $this->actingAs($this->admin)
        ->get(route('admin.logs.index', ['action' => 'deleted']))
        ->assertInertia(fn ($page) => $page
            ->has('activity.data', 1)
            ->where('activity.data.0.action', 'deleted'));
});
