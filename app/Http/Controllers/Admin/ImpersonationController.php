<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * "Bekijk als klant": an admin looks at the app exactly as one customer sees it, and switches back.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    /**
     * Admin only (route middleware): log in as the given customer, remembering who the admin was.
     */
    public function start(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $request->session()->put(self::SESSION_KEY, $request->user()->id);
        Auth::login($customer);

        return to_route('dashboard');
    }

    /**
     * Back to the admin who started. Only works when an admin really started it in this session.
     */
    public function stop(Request $request): RedirectResponse
    {
        $admin = User::query()->find($request->session()->pull(self::SESSION_KEY));
        abort_unless($admin?->isAdmin(), 403);

        $customer = $request->user();
        Auth::login($admin);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Je bent weer ingelogd als beheerder.']);

        return $customer?->isCustomer()
            ? to_route('admin.customers.show', $customer)
            : to_route('admin.dashboard');
    }
}
