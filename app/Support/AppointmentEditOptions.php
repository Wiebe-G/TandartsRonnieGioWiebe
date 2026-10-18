<?php

namespace App\Support;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;

/**
 * The choices in the admin "edit appointment" dialog.
 */
class AppointmentEditOptions
{
    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];

    /**
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        $staff = fn (string $role) => User::query()
            ->where('role_id', Role::idFor($role))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get(['id', 'role_id', 'firstname', 'lastname', 'status']);

        return [
            'dentists' => $staff(Role::DENTIST),
            'assistants' => Role::query()->where('name', Role::ASSISTANT)->exists() ? $staff(Role::ASSISTANT) : [],
            'rooms' => Room::query()->orderBy('name')->get(['room_id', 'name']),
            'statuses' => self::STATUSES,
        ];
    }
}
