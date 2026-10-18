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

class CustomerController extends Controller
{
    /**
     * Read: list all customers, optionally filtered by name or email.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $customers = User::query()
            ->where('role_id', Role::customerId())
            ->with('employee')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Create: show the form for a new customer.
     */
    public function create(): Response
    {
        return Inertia::render('admin/customers/create', [
            'dentists' => $this->dentists(),
        ]);
    }

    /**
     * Create: store a new customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $customer = User::create([
            ...$this->validated($request),
            'role_id' => Role::customerId(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klant aangemaakt.']);

        return to_route('admin.customers.show', $customer);
    }

    /**
     * Read: show a single customer with their appointments.
     */
    public function show(User $customer): Response
    {
        $this->ensureCustomer($customer);

        return Inertia::render('admin/customers/show', [
            'customer' => $customer->load('employee'),
            'appointments' => Appointment::query()
                ->where('customer_id', $customer->id)
                ->with(['dentist', 'treatments'])
                ->orderByDesc('date')
                ->orderByDesc('starttime')
                ->get(),
        ]);
    }

    /**
     * Update: show the edit form for a customer.
     */
    public function edit(User $customer): Response
    {
        $this->ensureCustomer($customer);

        return Inertia::render('admin/customers/edit', [
            'customer' => $customer,
            'dentists' => $this->dentists(),
        ]);
    }

    /**
     * Update: save the changes to a customer.
     */
    public function update(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        $customer->update($this->validated($request, $customer));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klant bijgewerkt.']);

        return to_route('admin.customers.show', $customer);
    }

    /**
     * Delete: remove a customer, unless they still have appointments.
     */
    public function destroy(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        // Appointments restrict deleting their customer, so they have to go first.
        if (Appointment::query()->where('customer_id', $customer->id)->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Deze klant heeft nog afspraken en kan niet verwijderd worden.']);

            return back();
        }

        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klant verwijderd.']);

        return to_route('admin.customers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $customer = null): array
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($customer)],
            'phonenumber' => ['nullable', 'string', 'max:20'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'employee_id' => ['nullable', Rule::exists(User::class, 'id')->where('role_id', Role::idFor(Role::DENTIST))],
            // A new customer needs a password; when editing, leaving it empty keeps the current one.
            'password' => [$customer ? 'nullable' : 'required', 'string', Password::default()],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless((int) $user->role_id === Role::customerId(), 404);
    }

    private function dentists()
    {
        return User::query()
            ->where('role_id', Role::idFor(Role::DENTIST))
            ->orderBy('lastname')
            ->get(['id', 'firstname', 'lastname']);
    }
}
