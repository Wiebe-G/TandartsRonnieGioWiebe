<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Read: list all employees, optionally filtered by name, email or role.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $roleId = $request->integer('role') ?: null;

        $employees = User::query()
            ->where('role_id', '!=', Role::customerId())
            ->with('role')
            ->withCount('customers')
            ->when($roleId, fn ($query) => $query->where('role_id', $roleId))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/employees/index', [
            'employees' => $employees,
            'roles' => $this->roles(),
            'filters' => ['search' => $search, 'role' => $roleId],
        ]);
    }

    /**
     * Create: show the form for a new employee.
     */
    public function create(): Response
    {
        return Inertia::render('admin/employees/create', [
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Create: store a new employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $employee = User::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medewerker aangemaakt.']);

        return to_route('admin.employees.show', $employee);
    }

    /**
     * Read: show a single employee with their customers and upcoming appointments.
     */
    public function show(User $employee): Response
    {
        $this->ensureEmployee($employee);

        return Inertia::render('admin/employees/show', [
            'employee' => $employee->load('role'),
            'customers' => $employee->customers()->orderBy('lastname')->get(),
            'appointments' => Appointment::query()
                ->where(fn ($query) => $query
                    ->where('dentist_id', $employee->id)
                    ->orWhere('assistant_id', $employee->id))
                ->whereDate('date', '>=', today())
                ->with(['customer', 'treatments'])
                ->orderBy('date')
                ->orderBy('starttime')
                ->get(),
        ]);
    }

    /**
     * Update: show the edit form for an employee.
     */
    public function edit(User $employee): Response
    {
        $this->ensureEmployee($employee);

        return Inertia::render('admin/employees/edit', [
            'employee' => $employee,
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Update: save the changes to an employee.
     */
    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->ensureEmployee($employee);

        $validated = $this->validated($request, $employee);

        // Stop an admin from locking themselves out of the admin pages.
        if ($employee->is($request->user()) && (int) $validated['role_id'] !== (int) $employee->role_id) {
            return back()->withErrors(['role_id' => 'Je kunt je eigen rol niet wijzigen.']);
        }

        $employee->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medewerker bijgewerkt.']);

        return to_route('admin.employees.show', $employee);
    }

    /**
     * Delete: remove an employee, unless they are still the dentist on appointments.
     */
    public function destroy(Request $request, User $employee): RedirectResponse
    {
        $this->ensureEmployee($employee);

        if ($employee->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Je kunt je eigen account hier niet verwijderen.']);

            return back();
        }

        // Appointments restrict deleting their dentist; assistants and assigned customers are cleared automatically.
        if (Appointment::query()->where('dentist_id', $employee->id)->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Deze medewerker is nog tandarts bij afspraken en kan niet verwijderd worden.']);

            return back();
        }

        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medewerker verwijderd.']);

        return to_route('admin.employees.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $employee = null): array
    {
        $validated = $request->validate([
            'role_id' => ['required', Rule::exists(Role::class, 'role_id')->whereNot('name', Role::CUSTOMER)],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($employee)],
            'phonenumber' => ['nullable', 'string', 'max:20'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            // A new employee needs a password; when editing, leaving it empty keeps the current one.
            'password' => [$employee ? 'nullable' : 'required', 'string', Password::default()],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }

    private function ensureEmployee(User $user): void
    {
        abort_if((int) $user->role_id === Role::customerId(), 404);
    }

    private function roles()
    {
        return Role::query()
            ->where('name', '!=', Role::CUSTOMER)
            ->orderBy('role_id')
            ->get(['role_id', 'name']);
    }
}
