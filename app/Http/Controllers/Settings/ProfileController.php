<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Postcode;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $address = $request->user()->address;

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'address' => $address ? [
                'street' => $address->street,
                'house_number' => $address->house_number,
                'postcode' => $address->postcode(),
                'city' => $address->city,
            ] : null,
            'dentists' => $request->user()->isCustomer() ? User::dentistOptions() : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->safe()->only(['firstname', 'lastname', 'email', 'phonenumber', 'birthday']));
        $request->user()->adress = $this->saveAddress($request);

        // Only customers choose a dentist; for employees this field means something else.
        if ($request->user()->isCustomer()) {
            $request->user()->employee_id = $request->integer('employee_id') ?: null;
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Store the address from the form and return its key, or null when it was left empty.
     */
    private function saveAddress(ProfileUpdateRequest $request): ?string
    {
        if (! $request->filled('postcode')) {
            return null;
        }

        $key = Postcode::keyFor($request->string('postcode'), $request->string('house_number'));

        Postcode::updateOrCreate(['adress' => $key], [
            'street' => $request->string('street'),
            'house_number' => $request->string('house_number')->trim(),
            'city' => $request->string('city'),
            'residence' => $request->string('city'),
        ]);

        return $key;
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
