<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __invoke(): Response
    {
        $customerRoleId = Role::customerId();

        $customers = User::query()->where('role_id', $customerRoleId);
        $employees = User::query()->where('role_id', '!=', $customerRoleId);

        return Inertia::render('admin/user-management', [
            'stats' => [
                'customers' => (clone $customers)->count(),
                'activeCustomers' => (clone $customers)->where('status', 'active')->count(),
                'employees' => (clone $employees)->count(),
                'appointmentsToday' => Appointment::query()->whereDate('date', today())->count(),
            ],
            // Employees per role, so the admin sees how the practice is staffed.
            'roles' => Role::query()
                ->where('name', '!=', Role::CUSTOMER)
                ->withCount('users')
                ->orderBy('role_id')
                ->get(['role_id', 'name']),
            'latestCustomers' => (clone $customers)->latest()->limit(5)->get(),
            'latestEmployees' => (clone $employees)->with('role')->latest()->limit(5)->get(),
        ]);
    }
}
