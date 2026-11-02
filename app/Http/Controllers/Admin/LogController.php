<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The log page for admins: who did what in the application.
 */
class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'login', 'logout', 'login_failed'])],
        ]);
        $search = trim($filters['search'] ?? '');

        return Inertia::render('admin/logs', [
            'filters' => [
                'search' => $search,
                'action' => $filters['action'] ?? '',
            ],
            'activity' => ActivityLog::query()
                ->with('user:id,firstname,lastname')
                ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%"))))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }
}
