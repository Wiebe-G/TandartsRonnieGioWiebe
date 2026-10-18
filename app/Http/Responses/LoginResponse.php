<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins start on their own dashboard after logging in; everyone else on the normal one.
 * A remembered page from before logging in is only used when the user may open it,
 * otherwise someone could end up on a 403 (e.g. an admin page after logging in as a dentist).
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $home = $user?->isAdmin() ? route('admin.dashboard') : Fortify::redirects('login');
        $intended = $request->session()->pull('url.intended');

        return redirect()->to($intended && $this->mayOpen($user, $intended) ? $intended : $home);
    }

    /**
     * Mirrors the Admin and Employee middleware on the routes.
     */
    private function mayOpen(?User $user, string $url): bool
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        if (Str::startsWith($path, '/admin')) {
            return (bool) $user?->isAdmin();
        }

        if (Str::startsWith($path, ['/appointments', '/treatments'])) {
            return $user !== null && ! $user->isCustomer();
        }

        return true;
    }
}
